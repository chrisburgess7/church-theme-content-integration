<?php
/**
 * Plugin Name: Church Theme Content Integration
 * Plugin URI:
 * Description: Provides integration functionality between the Church Theme Content plugin and other church-related service providers.
 * Version: 0.2
 * Author: Chris Burgess
 * Author URI:
 * License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: church-theme-content-integration
 */

// No direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// TODO: clearer error handling if settings not filled correctly

/**
 * Main class
 */
class Church_Theme_Content_Integration {

	public static $DB_VERSION = '0.2';
	public static $CSS_VERSION = '0.1';
	public static $JS_VERSION = '0.1';
	public static $CTC_PLUGIN_NAME = 'church-theme-content';
	public static $CTC_PLUGIN_FILE = 'church-theme-content/church-theme-content.php';

	public static $PLUGIN_NAME = '';
	public static $PLUGIN_VERSION = '';
	public static $PLUGIN_PATH = '';
	public static $PLUGIN_DIR = '';
	public static $PLUGIN_FILE = '';
	public static $ADMIN_DIR = '';
	public static $ADMIN_PATH = '';
	public static $LANG_DIR = '';

	public static $CONFIG_CAPABILITY = 'manage_options';
	public static $RUN_SYNC_CAPABILITY = 'ctci_run_sync';

	public static $CONFIG_GROUP = 'ctci_config_options';
	public static $ENABLE_OPT_SECTION = 'ctci_enable_modules_section';
	public static $ENABLE_OPT_PAGE = 'ctci_enable_modules_page';

	public static $RUN_PAGE = 'ctci-main-options';

	public static $TEXT_DOMAIN = 'church-theme-content-integration';

	/**
	 * Plugin data from get_plugins()
	 *
	 * @var object
	 */
	public $plugin_data;

	/**
	 * Includes to load
	 *
	 * @var array
	 */
	public $includes;

	/**
	 * @var CTCI_DataProviderInterface[]
	 */
	private $dataProviders = array();

	/**
	 * @var CTCI_OperationInterface[]
	 */
	private $operationTypes = array();

	/**
	 * @var CTCI_WPALInterface
	 */
	private $wpal;

	/**
	 * @var CTCI_StatusTrackerInterface
	 */
	private $statusTracker;

	/**
	 * @var CTCI_Session
	 */
	private $session;

	/**
	 * @var CTCI_HTTPVariablesManager
	 */
	private $httpVarManager;

	/**
	 * @var CTCI_HtmlHelperInterface
	 */
	private $htmlHelper;

	private $enableModuleFields = array();

	private $settings = array();

	public static function getLogFileName( $type = 'txt' ) {
		$path = plugin_dir_path( __FILE__ ) . 'log';
		if ( $type === 'html' ) {
			$path .= '_html';
		}
		$path .= '.txt';
		return $path;
	}

	/**
	 * Constructor
	 *
	 * Add actions for methods that define constants and load includes.
	 *
	 */
	public function __construct() {

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

		// Set plugin data
		add_action( 'plugins_loaded', array( &$this, 'set_plugin_data' ), 1 );

		// init variables
		add_action( 'plugins_loaded', array( &$this, 'init_plugin_variables' ), 2 );

		// Load this plugins service provider modules
		add_action( 'plugins_loaded', array( &$this, 'load_modules' ), 3 );

		// Load language file
		add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ), 4 );
		
		// Set includes
		add_action( 'plugins_loaded', array( &$this, 'set_includes' ), 5 );

		// Load includes
		add_action( 'plugins_loaded', array( &$this, 'load_includes' ), 6 );

		// Load objects
		add_action( 'plugins_loaded', array( &$this, 'load_objects' ), 7 );

		// init objects
		add_action( 'plugins_loaded', array( &$this, 'init_objects' ), 8 );

		// Set up run module actions
		add_action( 'plugins_loaded', array( &$this, 'load_run_actions' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'build_admin_menu' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'process_settings_export' ) );
			add_action( 'admin_init', array( $this, 'process_settings_import' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'admin_notices', array( &$this, 'system_checks' ) );
			// add action to handle a ctc group being deleted
			add_action( 'delete_ctc_person_group', array( &$this, 'delete_ctc_person_group' ) );
		}
	}

	public function delete_ctc_person_group( $term_id ) {
		$this->init_plugin_variables();
		$this->set_includes();
		$this->load_includes();
		$wpal = new CTCI_WPAL();
		$wpal->deleteCTCGroupAttachRecord( $term_id );
	}

	public function activation() {
		$this->setup_db();
		$this->add_capabilities();
		$this->load_default_settings();
	}

	public function setup_db() {
		global $wpdb;
		$tableName = $wpdb->prefix . 'ctci_ctcgroup_connect';
		$sql = "CREATE TABLE $tableName (
			term_id bigint(20) NOT NULL,
			data_provider varchar(16) NOT NULL,
			provider_group_id varchar(32) NOT NULL,
			KEY term_id (term_id)
		);";

		$syncStatusTable = $wpdb->prefix . 'ctci_syncstatus';
		$sql .= "CREATE TABLE $syncStatusTable (
			id int NOT NULL,
			message varchar(256),
			errors int,
			error_messages varchar(256),
			warnings int,
			warning_messages varchar(256),
		 	KEY id (id)
		);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		// the sync status table needs to have one row with PK 1
		$syncRowCount = $wpdb->get_var( "SELECT COUNT(*) FROM $syncStatusTable" );
		if ($syncRowCount < 1) {
			$wpdb->insert( $syncStatusTable, array( 'id' => 1, 'errors' => 0, 'warnings' => 0 ) );
		}

		add_option( "ctci_db_version", self::$DB_VERSION);
	}

	protected function add_capabilities() {
		$roles = get_editable_roles();
		/** @var $role WP_Role */
		foreach ($GLOBALS['wp_roles']->role_objects as $key => $role) {
			if (isset($roles[$key]) && $role->has_cap('edit_others_posts')){
				$role->add_cap(self::$RUN_SYNC_CAPABILITY);
			}
		}
	}

	protected function load_default_settings() {
		$this->init_plugin_variables();
		$this->load_modules();
		$this->set_includes();
		$this->load_includes();
		$this->load_objects();
		$this->load_global_settings();

		$option = get_option( self::$CONFIG_GROUP );
		if ( ! is_array( $option ) ) {
			$defaults = array();
			foreach ( $this->enableModuleFields as $setting ) {
				$defaults[ $setting[0] ] = $setting[3];
			}
			foreach ( $this->settings as $setting ) {
				$defaults[ $setting[0] ] = $setting[3];
			}
			add_option( self::$CONFIG_GROUP, $defaults );
		}

		foreach ( $this->dataProviders as $dataProvider ) {
			$dataProvider->loadDefaultSettings();
		}
	}

	protected function load_global_settings() {
		// for each data provider, create an enable button for each function it supports
		foreach ( $this->dataProviders as $dataProvider ) {
			foreach ( $this->operationTypes as $operation ) {
				if ( $dataProvider->isDataProviderFor( $operation::getTag() ) ) {
					$fieldName = $this->get_operation_enabled_option( $dataProvider->getTag(), $operation::getTag() );
					/* translators: button label for enabling a module for a given data provider and operation */
					$this->enableModuleFields[] = array(
						$fieldName,
						sprintf(
							__( 'Enable %1$s %2$s', self::$TEXT_DOMAIN),
							$dataProvider->getHumanReadableName(),
							$operation->getHumanReadableName()
						),
						array( $this, 'show_module_enable_field' ),
						'T'
					);
				}
			}
		}

		// add any further global settings to this list, and it should get updated throughout
		$this->settings = array(
			array( 'debug_mode', __( 'Debug Mode', self::$TEXT_DOMAIN ), array( $this, 'show_debug_option'), 'F' )
		);
	}

	public function deactivation() {
		$this->remove_cap();
	}

	// Remove the plugin-specific custom capability
	protected function remove_cap() {
		$roles = get_editable_roles();
		/** @var $role WP_Role */
		foreach ($GLOBALS['wp_roles']->role_objects as $key => $role) {
			if (isset($roles[$key]) && $role->has_cap(self::$RUN_SYNC_CAPABILITY))
				$role->remove_cap(self::$RUN_SYNC_CAPABILITY);
		}
	}

	/**
	 * Set plugin data
	 *
	 * This data is used by constants.
	 *
	 * @since 0.9
	 * @access public
	 */
	public function set_plugin_data() {

		// Load plugin.php if get_plugins() not available
		if ( !function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get path to plugin's directory
		$plugin_dir = plugin_basename( dirname( __FILE__ ) );

		// Get plugin data
		$plugin_data = current( get_plugins( '/' . $plugin_dir ) );

		// Set plugin data
		$this->plugin_data = apply_filters( 'ctci_plugin_data', $plugin_data );

	}

	/**
	 * Define constants
	 *
	 * @since 0.9
	 * @access public
	 */
	public function init_plugin_variables() {
		self::$PLUGIN_NAME = $this->plugin_data[ 'Name' ];
		self::$PLUGIN_VERSION = $this->plugin_data[ 'Version' ];
		self::$PLUGIN_PATH = untrailingslashit( plugin_dir_path( __FILE__ ) );
		self::$PLUGIN_DIR = dirname( plugin_basename( __FILE__ ) );
		self::$PLUGIN_FILE = __FILE__;
		self::$ADMIN_DIR = 'admin';
		self::$ADMIN_PATH = trailingslashit( self::$PLUGIN_PATH ) . self::$ADMIN_DIR;
		self::$CTC_PLUGIN_NAME = 'church-theme-content';
		self::$CTC_PLUGIN_FILE = 'church-theme-content/church-theme-content.php';
		self::$LANG_DIR = 'languages';
	}

	public function load_modules() {
		// add any sub-folders of admin that don't correspond to a service provider
		$blacklist = array('.', '..', 'css', 'js');
		$files = scandir( self::$ADMIN_PATH );
		foreach ( $files as $file ) {
			if ( ! in_array( $file, $blacklist ) ) {
				$fullFilename = trailingslashit( self::$ADMIN_PATH ) . $file;
				if ( is_dir( $fullFilename ) ) {
					$providerClassFile = trailingslashit( $fullFilename ) . $file . '.php';
					if ( file_exists( $providerClassFile ) ) {
						require_once $providerClassFile;
						$class = str_replace( '-', '_', $file );
						$class = "CTCI_$class";
						if ( class_exists( $class ) && in_array( 'CTCI_DataProviderInterface', class_implements( $class ) ) ) {
							/** @var CTCI_DataProviderInterface $obj */
							$obj = new $class;
							$this->dataProviders[ $file ] = $obj;
						}
					}
				}
			}
		}
	}

	/**
	 * Load language file
	 *
	 * This will load the MO file for the current locale.
	 * The translation file must be named church-theme-content-$locale.mo.
	 *
	 * First it will check to see if the MO file exists in wp-content/languages/plugins.
	 * If not, then the 'languages' directory inside the plugin will be used.
	 * It is ideal to keep translation files outside of the plugin to avoid loss during updates.
	 *
    */
	public function load_textdomain() {
	    // WordPress core locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), self::$TEXT_DOMAIN );

		// WordPress 3.6 and earlier don't auto-load from wp-content/languages, so check and load manually
		// http://core.trac.wordpress.org/changeset/22346
		$external_mofile = WP_LANG_DIR . '/plugins/'. self::$TEXT_DOMAIN . '-' . $locale . '.mo';
		if ( get_bloginfo( 'version' ) <= 3.6 && file_exists( $external_mofile ) ) {
			// external translation exists
			load_textdomain( self::$TEXT_DOMAIN, $external_mofile );
		} else {
			// Load normally
			// Either using WordPress 3.7+ or older version with external translation
			$languages_dir = self::$PLUGIN_DIR . '/' . trailingslashit( self::$LANG_DIR ); // ensure trailing slash
			load_plugin_textdomain( self::$TEXT_DOMAIN, false, $languages_dir );
		}
	}


	/**
	 * Set includes
	 *
	 */
	public function set_includes() {
		$includes = array(

			// Frontend or admin
			'always' => array(

			),

			// Admin only
			'admin' => array(

				self::$ADMIN_DIR . '/interface-ctc-group.php',
				self::$ADMIN_DIR . '/interface-ctc-person.php',
				self::$ADMIN_DIR . '/interface-data-provider.php',
				self::$ADMIN_DIR . '/interface-general-settings.php',
				self::$ADMIN_DIR . '/interface-html-helper.php',
				self::$ADMIN_DIR . '/interface-http-variables-manager.php',
				self::$ADMIN_DIR . '/interface-logger.php',
				self::$ADMIN_DIR . '/interface-operation.php',
				self::$ADMIN_DIR . '/interface-people-data-provider.php',
				self::$ADMIN_DIR . '/interface-people-group.php',
				self::$ADMIN_DIR . '/interface-person.php',
				self::$ADMIN_DIR . '/interface-status-tracker.php',
				self::$ADMIN_DIR . '/interface-wpal.php',
				self::$ADMIN_DIR . '/class-ctc-group.php',
				self::$ADMIN_DIR . '/class-ctc-person.php',
				self::$ADMIN_DIR . '/class-data-provider.php',
				self::$ADMIN_DIR . '/class-html-helper.php',
				self::$ADMIN_DIR . '/class-http-variables-manager.php',
				self::$ADMIN_DIR . '/class-logger.php',
				self::$ADMIN_DIR . '/class-module-process.php',
				self::$ADMIN_DIR . '/class-people-group.php',
				self::$ADMIN_DIR . '/class-people-sync.php',
				self::$ADMIN_DIR . '/class-person.php',
				self::$ADMIN_DIR . '/class-session.php',
				self::$ADMIN_DIR . '/class-status-tracker.php',
				self::$ADMIN_DIR . '/class-wpal.php',

			),
			// Frontend only

			'frontend' => array (

			),
		);

		// add include files from modules
		foreach ( $this->dataProviders as $folder => $dataProvider ) {
			$modIncludesAlways = $dataProvider->getIncludes( 'always' );
			foreach ( $modIncludesAlways as $includeFile ) {
				$includes['always'][] = trailingslashit( self::$ADMIN_DIR ) . trailingslashit( $folder ) . $includeFile;
			}
			$modIncludesAdmin = $dataProvider->getIncludes( 'admin' );
			foreach ( $modIncludesAdmin as $includeFile ) {
				$includes['admin'][] = trailingslashit( self::$ADMIN_DIR ) . trailingslashit( $folder ) . $includeFile;
			}
			$modIncludesFrontend = $dataProvider->getIncludes( 'frontend' );
			foreach ( $modIncludesFrontend as $includeFile ) {
				$includes['admin'][] = trailingslashit( self::$ADMIN_DIR ) . trailingslashit( $folder ) . $includeFile;
			}
		}

		$this->includes = apply_filters( 'ctci_includes', $includes );
	}

	/**
	 * Load includes
	 *
	 * Include files based on whether or not condition is met.
	 *
	 * @since 0.9
	 * @access public
	 */
	public function load_includes() {
		// Get includes
		$includes = $this->includes;

		// Loop conditions
		foreach ( $includes as $condition => $files ) {
			$do_includes = false;
			// Check condition
			switch ( $condition ) {
				// Admin Only
				case 'admin':
					if ( is_admin() ) {
						$do_includes = true;
					}
					break;
				// Frontend Only
				case 'frontend':
					if ( !is_admin() ) {
						$do_includes = true;
					}
					break;
				// Admin or Frontend (always)
				default:
					$do_includes = true;
					break;
			}

			// Loop files if condition met
			if ( $do_includes ) {
				foreach ( $files as $file ) {
					require_once trailingslashit( self::$PLUGIN_PATH ) . $file;
				}
			}
		}
	}

	public function load_objects() {
		if ( ! is_object( $this->wpal ) ) {
			$this->wpal = new CTCI_WPAL();
		}
		if ( ! is_object( $this->statusTracker ) ) {
			$this->statusTracker = new CTCI_StatusTracker( $this->wpal, new CTCI_Logger() );
		}
		$this->session = new CTCI_Session( new CTCI_PhpSessionAdapter() );
		$this->httpVarManager = new CTCI_HTTPVariablesManager();
		$this->htmlHelper = new CTCI_HtmlHelper( array( $this, 'get_run_module_key' ) );

		// a list of all operations currently supported
		// need to add new ones here once implemented
		$this->operationTypes = array(
			new CTCI_PeopleSync( $this->wpal, $this->statusTracker )
		);
	}

	public function init_objects() {
		$options = get_option( self::$CONFIG_GROUP );
		if ( $options['debug_mode'] === 'T' ) {
			$this->statusTracker->includeExceptions();
            foreach ( $this->dataProviders as $dataProvider ) {
                $dataProvider->setDebugMode();
            }
		}

		foreach ( $this->dataProviders as $dataProvider ) {
			$dataProvider->initOnLoad( $this->session, $this->httpVarManager, $this->htmlHelper );
		}
	}

	public function load_run_actions() {
		foreach ( $this->dataProviders as $dataProvider ) {
			foreach ( $this->operationTypes as $operation ) {
				if ( $dataProvider->isDataProviderFor( $operation::getTag() ) ) {
					$process = new CTCI_ModuleProcess( $this->statusTracker, $this->wpal );
					$process->addDataProvider( $dataProvider );
					$operationInstance = clone $operation;
					$process->addOperation( $operationInstance );
					$moduleKey = $this->get_run_module_key( $dataProvider->getTag(), $operation::getTag() );
					add_action(
						'wp_ajax_' . $moduleKey,
						array( $process, 'runAJAX' )
					);
				}
			}
		}
		// add the run action for retrieving the log file
		add_action( 'wp_ajax_ctci_getlog', array( $this, 'get_log_file_ajax' ) );
		// add action for polling sync status
		add_action( 'wp_ajax_ctci_check_status', array( $this, 'get_sync_status' ) );
	}

	public function get_log_file_ajax() {
		$str = file_get_contents( self::getLogFileName('html') );
		if ( $str === false ) {
			echo 'Error: file could not be opened';
		} else {
			echo $str;
		}
		die();
	}

	public function get_sync_status() {
		require_once dirname( __FILE__ ) . '/admin/class-wpal.php';
		$wpal = new CTCI_WPAL();

		try{
			$json = $wpal->getSyncStatusAsJSON();
			if ( $json !== false ) {
				echo $json;
			}
		} catch ( Exception $e ) {}

		die();
	}

	public function enqueue_scripts() {
		wp_register_style( 'ctci-style', plugins_url( '/admin/css/style.css', __FILE__ ), array(), self::$CSS_VERSION, 'all' );
		wp_register_script( 'ctci-sprintf-js', plugins_url( '/admin/js/sprintf.js', __FILE__ ), array(), self::$JS_VERSION );
		wp_register_script( 'ctci-run-status', plugins_url( '/admin/js/CTCIRunStatus.js', __FILE__ ), array(), self::$JS_VERSION );
		wp_register_script( 'ctci-scripts', plugins_url( '/admin/js/scripts.js', __FILE__ ), array( 'jquery', 'ctci-sprintf-js', 'ctci-run-status' ), self::$JS_VERSION );
		wp_enqueue_style( 'ctci-style' );

		// create i18n strings for our JS
		// note that for testing, these have been replicated in the Spec.html test files, as this code isn't available in test
		// make sure that these are the same when testing!!!
		$translations = array(
			'message_1_error_with_message' => __( '%1$s. Error: %2$s', self::$TEXT_DOMAIN ),
			'message_x_errors_with_message' => __( '%1$s. Error: %2$s (%3$d errors in total, see log for details)', self::$TEXT_DOMAIN ),
			'message_1_error_no_message' => __( '%1$s. An error has occurred. See log for details.', self::$TEXT_DOMAIN ),
			'message_x_errors_no_message' => __( '%1$s. %2$s errors have occurred. See log for details.', self::$TEXT_DOMAIN ),
			'message_1_warning_with_message' => __( '%1$s. Warning: %2$s', self::$TEXT_DOMAIN ),
			'message_x_warnings_with_message' => __( '%1$s. Warning: %2$s (%3$d warnings in total, see log for details)', self::$TEXT_DOMAIN ),
			'message_1_warning_no_message' => __( '%1$s. A warning has occurred. See log for details.', self::$TEXT_DOMAIN ),
			'message_x_warnings_no_message' => __( '%1$s. %2$s warnings have occurred. See log for details.', self::$TEXT_DOMAIN ),
			'message_errors_warnings_both_message' => __( '%1$s. %2$s error(s) have occurred. Error: %3$s. %4$s warning(s) have occurred. Warning: %5$s.', self::$TEXT_DOMAIN ),
			'message_errors_warnings_error_message' => __( '%1$s. %2$s error(s) have occurred. Error: %3$s. %4$s warning(s) have occurred, see log.', self::$TEXT_DOMAIN ),
			'message_errors_warnings_warning_message' => __( '%1$s. %2$s error(s) have occurred, see log. %3$s warning(s) have occurred. Warning: %4$s.', self::$TEXT_DOMAIN ),
			'message_errors_warnings_no_message' => __( '%1$s. %2$s error(s) have occurred, see log. %3$s warning(s) have occurred, see log.', self::$TEXT_DOMAIN ),
			'ajax_response_not_json_upon_completion' => __( 'Sorry, but a problem has occurred retrieving the status from the server. The log might tell you if the sync has succeeded.', self::$TEXT_DOMAIN ),
		);

		wp_localize_script( 'ctci-scripts', 'ctci_translations', $translations );
		wp_enqueue_script( 'ctci-sprintf-js' );
		wp_enqueue_script( 'ctci-run-status' );
		wp_enqueue_script( 'ctci-scripts' );

	}

	public function system_checks() {
		if ( ! $this->isCTCActive() ) {
			printf(
				'<div class="error"><p>%s</p></div>',
				__( 'Church Theme Content Integration: The Church Theme Content plugin from <a href="http://churchthemes.com">churchthemes.com</a> must be installed and activated before Church Theme Content Integration can be used.',
					self::$TEXT_DOMAIN
				)
			);
		}
		if ( ! $this->curlAvailable() ) {
			printf(
				'<div class="error"><p>%s</p></div>',
				__( 'Church Theme Content Integration: This plugin requires that the <a href="http://www.php.net/manual/en/book.curl.php">PHP curl library</a> be installed on your web server.',
					self::$TEXT_DOMAIN
				)
			);
		}
	}

	protected function isCTCActive() {
		return is_plugin_active( self::$CTC_PLUGIN_FILE );
	}

	protected function curlAvailable() {
		return extension_loaded( 'curl' );
	}

	public function build_admin_menu() {
		add_menu_page(
			__('CTC Integration Options', self::$TEXT_DOMAIN),
			__('CTC Integration', self::$TEXT_DOMAIN),
			self::$RUN_SYNC_CAPABILITY,
			self::$RUN_PAGE,
			array( $this, 'show_options_home_page' )
		);

		add_submenu_page(
			'ctci-main-options',
			__('Church Theme Content Integration Configuration', self::$TEXT_DOMAIN),
			__('Configuration', self::$TEXT_DOMAIN),
			self::$CONFIG_CAPABILITY,
			'ctci-configuration',
			array( $this, 'show_configuration_page')
		);

		foreach ( $this->dataProviders as $dataProvider ) {
			add_submenu_page(
				'ctci-main-options',
				sprintf( __( '%s Settings', self::$TEXT_DOMAIN), $dataProvider->getHumanReadableName() ),
				$dataProvider->getHumanReadableName(),
				self::$CONFIG_CAPABILITY,
				'ctci-' . $dataProvider->getTag() . '-options',
				array( $dataProvider, 'showSettingsPage' )
			);
		}
	}

	public function show_options_home_page() {
		if ( ! current_user_can( self::$RUN_SYNC_CAPABILITY ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$configOptions = get_option( self::$CONFIG_GROUP );
		echo '<div class="wrap">';
		echo '<h2>' . __( 'Church Theme Content Integration', self::$TEXT_DOMAIN ) . '</h2>';
		echo '<div id="ctci-run-page">';

		foreach ( $this->dataProviders as $dataProvider ) {
			echo '<h3>' . $dataProvider->getHumanReadableName() . '</h3>';
			foreach ( $this->operationTypes as $operation ) {
				if ( $dataProvider->isDataProviderFor( $operation::getTag() ) ) {
					$enabledOpt = $this->get_operation_enabled_option( $dataProvider->getTag(), $operation::getTag() );
					$enabled = false;
					if ( $this->isCTCActive() && $this->curlAvailable() && isset( $configOptions[ $enabledOpt ] ) && $configOptions[ $enabledOpt ] === 'T' ) {
						$enabled = true;
					}
					echo '<div class="ctci-run-section">';
					echo '<div class="ctci-run-button">';
					$status = true;
					switch ( $dataProvider->getRunButtonHandlerType() ) {
						case CTCI_DataProviderInterface::RUNBUTTON_CUSTOM:
							$status = $dataProvider->showSyncButtonFor( $operation, $enabled );
							break;
						default:
						case CTCI_DataProviderInterface::RUNBUTTON_AJAX:
							$this->htmlHelper->showAJAXRunButtonFor( $dataProvider, $operation, $enabled );
							break;
					}
					echo '</div>'; // run button
					echo '<div class="ctci-run-indicator"></div>';
					if ( $status === true ) {
						echo '<div class="ctci-run-update"></div>';
					} else {
						printf( '<div class="ctci-run-update"><div class="error">%s</div></div>', $status );
					}
					echo '</div>'; // run section
				}
			}
		}

		echo '</div>'; // end run page

		// view log section
		echo '<div id="ctci-log-viewer">';
		$this->htmlHelper->showAJAXButton( 'View Log', 'ctci_getlog' );
		echo '<div class="ctci-run-indicator"></div>';
		echo '<div id="ctci-message-log"></div>';
		echo '</div>';
		echo '</div>'; // end wrap
	}

	public function show_configuration_page() {
		if ( ! current_user_can( self::$CONFIG_CAPABILITY ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2><?php _e('Church Theme Content Integration Configuration', self::$TEXT_DOMAIN) ?></h2>
			<!--suppress HtmlUnknownTarget -->
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( self::$CONFIG_GROUP );
				do_settings_sections( self::$ENABLE_OPT_PAGE );
				submit_button();
				?>
			</form>
			<div class="metabox-holder">
				<div class="postbox">
					<h3><span><?php _e( 'Export Settings' ); ?></span></h3>
					<div class="inside">
						<p><?php _e( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.', self::$TEXT_DOMAIN ); ?></p>
						<form method="post">
							<p><input type="hidden" name="ctci_action" value="export_settings" /></p>
							<p>
								<?php wp_nonce_field( 'ctci_export_nonce', 'ctci_export_nonce' ); ?>
								<?php submit_button( __( 'Export', self::$TEXT_DOMAIN ), 'secondary', 'submit', false ); ?>
							</p>
						</form>
					</div><!-- .inside -->
				</div><!-- .postbox -->
				<?php if ( $this->httpVarManager->hasGetVar('ctci-import-status') ) : ?>
					<?php
						$status = $this->httpVarManager->getGetVar('ctci-import-status');
						if ( $status == 0 ) :
					?>
						<div class="updated"><p>Settings successfully imported.</p></div>
					<?php elseif ( $status == 1 ) : ?>
						<div class="error">
						<p>
							<?php _e( 'Please upload a valid json file.', self::$TEXT_DOMAIN ); ?>
						</p>
						</div>
					<?php elseif ( $status == 2 ) : ?>
						<div class="error">
							<p>
								<?php _e( 'Please select a file to import.', self::$TEXT_DOMAIN ); ?>
							</p>
						</div>
					<?php elseif ( $status == 3 ) : ?>
						<div class="error">
							<p>
								<?php _e( 'The file selected was not exported from this plugin.', self::$TEXT_DOMAIN ); ?>
							</p>
						</div>
					<?php elseif ( $status == 4 ) : ?>
						<div class="error">
							<p>
								<?php _e( 'The file selected was exported from a different version of this plugin. Please make sure that you are exporting and importing from the same version.', self::$TEXT_DOMAIN ); ?>
							</p>
						</div>
					<?php endif; ?>
				<?php endif; ?>
				<div class="postbox">
					<h3><span><?php _e( 'Import Settings', self::$TEXT_DOMAIN ); ?></span></h3>
					<div class="inside">
						<p><?php _e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', self::$TEXT_DOMAIN ); ?></p>
						<form method="post" enctype="multipart/form-data">
							<p>
								<input type="file" name="import_file" />
							</p>
							<p>
								<input type="hidden" name="ctci_action" value="import_settings" />
								<?php wp_nonce_field( 'ctci_import_nonce', 'ctci_import_nonce' ); ?>
								<?php submit_button( __( 'Import', self::$TEXT_DOMAIN ), 'secondary', 'submit', false ); ?>
							</p>
						</form>
					</div><!-- .inside -->
				</div><!-- .postbox -->
			</div><!-- .metabox-holder -->
		</div>
	<?php
	}

	public function register_settings() {

		$this->load_global_settings();

		register_setting( self::$CONFIG_GROUP, self::$CONFIG_GROUP, array( $this, 'validate_config_options' ) );
		add_settings_section(
			self::$ENABLE_OPT_SECTION,
			/* translators: heading on the main configuration page for the section with checkboxes to enable each module */
			__( 'Enable Modules', self::$TEXT_DOMAIN ),
			array( $this, 'show_mod_enable_text'),
			self::$ENABLE_OPT_PAGE
		);

		// for each data provider, create an enable button for each function it supports
		foreach ( $this->enableModuleFields as $enableModField ) {
			add_settings_field(
				$enableModField[0], $enableModField[1], $enableModField[2],
				self::$ENABLE_OPT_PAGE,
				self::$ENABLE_OPT_SECTION,
				array(
					'fieldName' => $enableModField[0]
				)
			);
		}

		foreach ( $this->dataProviders as $dataProvider ) {
			$dataProvider->registerSettings();
		}

		foreach ( $this->settings as $settings ) {
			add_settings_field( $settings[0], $settings[1], $settings[2], self::$ENABLE_OPT_PAGE, self::$ENABLE_OPT_SECTION );
		}

	}

	public function show_debug_option() {
		$optionValues = get_option( self::$CONFIG_GROUP );
		$name = sprintf( "%s[%s]", self::$CONFIG_GROUP, 'debug_mode' );
		// this hidden field ensures the field is submitted even if unchecked
		// by default forms do not submit checkboxes not checked
		printf("<input type='hidden' name='%s' value='F' />", $name);
		printf(
			"<input id='%s' name='%s' type='checkbox' value='T' %s />",
			'debug_mode',
			$name,
			checked(
				isset( $optionValues[ 'debug_mode' ] ) &&
				$optionValues[ 'debug_mode' ] === 'T',
				true,
				false
			)
		);
	}

	public function show_module_enable_field( $args ) {
		$optionValues = get_option( self::$CONFIG_GROUP );
		$name = sprintf( "%s[%s]", self::$CONFIG_GROUP, $args['fieldName'] );
		// this hidden field ensures the field is submitted even if unchecked
		// by default forms do not submit checkboxes not checked
		printf("<input type='hidden' name='%s' value='F' />", $name);
		printf(
			"<input id='%s' name='%s' type='checkbox' value='T' %s />",
			$args['fieldName'],
			$name,
			checked(
				isset( $optionValues[ $args['fieldName'] ] ) &&
				$optionValues[ $args['fieldName'] ] === 'T',
				true,
				false
			)
		);
	}

	public function show_mod_enable_text() {
		// ...
	}

	public function validate_config_options( $settings ) {
		$newSettings = array();
		foreach ( $this->enableModuleFields as $field ) {
			$fieldName = $field[0];
			$newSettings[ $fieldName ] = trim( $settings[ $fieldName ] );
			if ( 'T' !== $newSettings[ $fieldName ] && 'F' !== $newSettings[ $fieldName ] ) {
				$newSettings[ $fieldName ] = 'F';
			}
		}
		$newSettings['debug_mode'] = trim( $settings['debug_mode'] );
		if ( $newSettings['debug_mode'] !== 'T' && $newSettings['debug_mode'] !== 'F' ) {
			$newSettings['debug_mode'] = 'F';
		}
		return $newSettings;
	}

	/**
	 * Return the name of the option for the enabled check box, for the given the provider folder/tag,
	 * and the function type.
	 * @param $providerTag
	 * @param $operation
	 * @return string
	 */
	private function get_operation_enabled_option( $providerTag, $operation ) {
		return "ctci_enable_{$providerTag}_$operation";
	}

	public static function get_run_module_key( $providerTag, $operation ) {
		return "ctci_run_{$providerTag}_$operation";
	}

	/**
	 * Process a settings export that generates a .json file of the shop settings
	 */
	public function process_settings_export() {

		if( empty( $_POST['ctci_action'] ) || 'export_settings' != $_POST['ctci_action'] )
			return;

		if( ! wp_verify_nonce( $_POST['ctci_export_nonce'], 'ctci_export_nonce' ) )
			return;

		if( ! current_user_can( self::$CONFIG_CAPABILITY ) )
			return;

		$settings = array();

		$settings['Plugin-Name'] = self::$PLUGIN_NAME;
		$settings['Version'] = self::$PLUGIN_VERSION;
		$settings[ self::$CONFIG_GROUP ] = get_option( self::$CONFIG_GROUP );

		foreach ( $this->dataProviders as $dataProvider ) {
			$optionName = $dataProvider->getSettingsGroupName();
			$settings[ $optionName ] = get_option( $optionName );
		}

		ignore_user_abort( true );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=ctci-settings-export-' . date( 'm-d-Y' ) . '.json' );
		header( "Expires: 0" );

		echo json_encode( $settings );
		exit;
	}

	/**
	 * Process a settings import from a json file
	 */
	public function process_settings_import() {

		if( empty( $_POST['ctci_action'] ) || 'import_settings' != $_POST['ctci_action'] )
			return;

		if( ! wp_verify_nonce( $_POST['ctci_import_nonce'], 'ctci_import_nonce' ) )
			return;

		if( ! current_user_can( self::$CONFIG_CAPABILITY ) )
			return;

		$extension = end( explode( '.', $_FILES['import_file']['name'] ) );

		if( $extension != 'json' ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ctci-configuration&ctci-import-status=1' ) );
			exit;
		}

		$import_file = $_FILES['import_file']['tmp_name'];

		if( empty( $import_file ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ctci-configuration&ctci-import-status=2' ) );
			exit;
		}

		// Retrieve the settings from the file and convert the json object to an array.
		$settings = json_decode( file_get_contents( $import_file ), true );

		if ( ! isset( $settings['Plugin-Name'] ) || $settings['Plugin-Name'] != self::$PLUGIN_NAME ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ctci-configuration&ctci-import-status=3' ) );
			exit;
		}

		if ( ! isset( $settings['Version'] ) || $settings['Version'] != self::$PLUGIN_VERSION ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ctci-configuration&ctci-import-status=4' ) );
			exit;
		}

		unset( $settings['Plugin-Name'] );
		unset( $settings['Version'] );

		foreach ( $settings as $settingName => $settingValue ) {
			update_option( $settingName, $settingValue );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=ctci-configuration&ctci-import-status=0' ) );
		exit;
	}
}

// Instantiate the main class
if ( is_admin() ) { // this if will need to go if adding functionality to anything other than admin
	new Church_Theme_Content_Integration();
}
