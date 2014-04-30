<?php
/**
 * Plugin Name: Church Theme Content Integration
 * Plugin URI:
 * Description: Provides integration functionality between the Church Theme Content plugin and other church-related service providers.
 * Version: 0.1
 * Author: Chris Burgess
 * Author URI:
 * License: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 */

// No direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class
 */
class Church_Theme_Content_Integration {

	public static $DB_VERSION = '0.1';
	public static $PLUGIN_PATH = '';
	public static $PLUGIN_DIR = '';
	public static $ADMIN_DIR = '';
	public static $ADMIN_PATH = '';

	public static $CONFIG_CAPABILITY = 'manage_options';
	public static $RUN_SYNC_CAPABILITY = 'ctci_run_sync';

	public static $CONFIG_GROUP = 'ctci_config_options';
	public static $ENABLE_OPT_SECTION = 'ctci_enable_modules_section';
	public static $ENABLE_OPT_PAGE = 'ctci_enable_modules_page';

	public static $RUN_PAGE = 'ctci-main-options';

	//public static $ENABLE_OPT_DISPLAY_CALLBACK_FUNCPFX = 'enable_opt_display_';

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
	 * @var CTCI_LoggerInterface
	 */
	private $logger;

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
		//add_action( 'plugins_loaded', array( &$this, 'set_plugin_data' ), 1 );

		// init variables
		add_action( 'plugins_loaded', array( &$this, 'init_plugin_variables' ), 1 );

		// Load this plugins service provider modules
		add_action( 'plugins_loaded', array( &$this, 'load_modules' ), 2 );

		// Load language file
		//add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ), 1 );
		
		// Set includes
		add_action( 'plugins_loaded', array( &$this, 'set_includes' ), 3 );

		// Load includes
		add_action( 'plugins_loaded', array( &$this, 'load_includes' ), 3 );

		// Load objects
		add_action( 'plugins_loaded', array( &$this, 'load_objects' ), 4 );

		// init objects
		add_action( 'plugins_loaded', array( &$this, 'init_objects' ), 5 );

		// Set up run module actions
		add_action( 'plugins_loaded', array( &$this, 'load_run_actions' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'build_admin_menu' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'admin_notices', array( &$this, 'system_checks' ) );
		}
	}

	public function activation() {
		$this->setup_db();
		$this->add_capabilities();
		$this->load_default_settings();
	}

	// TODO: add a delete action for removing this table
	public function setup_db() {
		global $wpdb;
		$tableName = $wpdb->prefix . 'ctci_ctcgroup_connect';
		$connectTableSQL = "CREATE TABLE $tableName (
			term_id bigint(20) NOT NULL,
			data_provider varchar(16) NOT NULL,
			provider_group_id varchar(32) NOT NULL,
			KEY term_id (term_id)
		);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($connectTableSQL);

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
					$this->enableModuleFields[] = array(
						$fieldName,
						__( sprintf('Enable %s %s', $dataProvider->getHumanReadableName(), $operation->getHumanReadableName() ),
							self::$TEXT_DOMAIN
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

	public function system_checks() {
		if ( ! $this->isCTCActive() ) {
			printf(
				'<div class="error"><p>%s</p></div>',
				__( 'Church Theme Content Integration: The Church Theme Content plugin from churchthemes.com
					must be installed and activated before Church Theme Content Integration can be used.',
					self::$TEXT_DOMAIN
				)
			);
		}
	}

	protected function isCTCActive() {
		return is_plugin_active( 'church-theme-content/church-theme-content.php' );
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
		$this->plugin_data = apply_filters( 'ctc_plugin_data', $plugin_data );

	}

	/**
	 * Define constants
	 *
	 * @since 0.9
	 * @access public
	 */
	public function init_plugin_variables() {
		self::$PLUGIN_PATH = untrailingslashit( plugin_dir_path( __FILE__ ) );
		self::$PLUGIN_DIR = dirname( plugin_basename( __FILE__ ) );
		self::$ADMIN_DIR = 'admin';
		self::$ADMIN_PATH = trailingslashit( self::$PLUGIN_PATH ) . self::$ADMIN_DIR;
		// Plugin details
		/*define( 'CTCI_VERSION', $this->plugin_data[ 'Version' ] ); // plugin version
		define( 'CTCI_NAME', $this->plugin_data[ 'Name' ] ); // plugin name
		define( 'CTCI_INFO_URL', $this->plugin_data[ 'PluginURI' ] ); // plugin's info page URL
		define( 'CTCI_FILE', __FILE__ ); // plugin's main file path
		define( 'CTCI_DIR', dirname( plugin_basename( CTCI_FILE ) ) ); // plugin's directory
		define( 'CTCI_PATH', untrailingslashit( plugin_dir_path( CTCI_FILE ) ) ); // plugin's directory
		define( 'CTCI_URL', untrailingslashit( plugin_dir_url( CTCI_FILE ) ) ); // plugin's directory URL
		define( 'CTCI_DB_VERSION', '0.1');

		// Directories
		define( 'CTCI_ADMIN_DIR', CTCI_DIR . '/admin' ); // admin directory
		define( 'CTCI_LANG_DIR', 'languages' ); // languages directory*/

	}

	public function load_modules() {
		// add any sub-folders of admin that don't correspond to a service provider
		$blacklist = array('.', '..');
		$files = scandir( self::$ADMIN_PATH );
		foreach ( $files as $file ) {
			if ( ! in_array( $file, $blacklist ) ) {
				$fullFilename = trailingslashit( self::$ADMIN_PATH ) . $file;
				if ( is_dir( $fullFilename ) ) {
					$providerClassFile = trailingslashit( $fullFilename ) . $file . '.php';
					if ( file_exists( $providerClassFile ) ) {
						require_once $providerClassFile;
						$class = str_replace( '-', '_', $file );
						// TODO: capitalization?
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
	 * If not, then the 'languages' direcory inside the plugin will be used.
	 * It is ideal to keep translation files outside of the plugin to avoid loss during updates.
	 *
	 * /
	 * public function load_textdomain() {
	 *
	 * // Textdomain
	 * $domain = 'church-theme-content';
	 *
	 * // WordPress core locale filter
	 * $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	 *
	 * // WordPress 3.6 and earlier don't auto-load from wp-content/languages, so check and load manually
	 * // http://core.trac.wordpress.org/changeset/22346
	 * $external_mofile = WP_LANG_DIR . '/plugins/'. $domain . '-' . $locale . '.mo';
	 * if ( get_bloginfo( 'version' ) <= 3.6 && file_exists( $external_mofile ) ) { // external translation exists
	 * load_textdomain( $domain, $external_mofile );
	 * }
	 *
	 * // Load normally
	 * // Either using WordPress 3.7+ or older version with external translation
	 * else {
	 * $languages_dir = CTC_DIR . '/' . trailingslashit( CTC_LANG_DIR ); // ensure trailing slash
	 * load_plugin_textdomain( $domain, false, $languages_dir );
	 * }
	 *
	 * }
	 *
	 * /**
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
		if ( ! is_object( $this->logger ) ) {
			$this->logger = new CTCI_Logger();
		}
		$this->session = new CTCI_Session( new CTCI_PhpSessionAdapter() );
		$this->httpVarManager = new CTCI_HTTPVariablesManager();
		$this->htmlHelper = new CTCI_HtmlHelper( array( $this, 'get_run_module_key' ) );

		// a list of all operations currently supported
		// need to add new ones here once implemented
		$this->operationTypes = array(
			new CTCI_PeopleSync( $this->wpal, $this->logger )
		);
	}

	public function init_objects() {
		$options = get_option( self::$CONFIG_GROUP );
		if ( $options['debug_mode'] === 'T' ) {
			$this->logger->includeExceptions();
		}

		foreach ( $this->dataProviders as $dataProvider ) {
			$dataProvider->initOnLoad( $this->session, $this->httpVarManager, $this->htmlHelper );
		}
	}

	public function load_run_actions() {
		foreach ( $this->dataProviders as $dataProvider ) {
			foreach ( $this->operationTypes as $operation ) {
				if ( $dataProvider->isDataProviderFor( $operation::getTag() ) ) {
					$process = new CTCI_ModuleProcess( $this->logger );
					$process->addDataProvider( $dataProvider );
					$operationInstance = clone $operation;
					$process->addOperation( $operationInstance );
					$moduleKey = $this->get_run_module_key( $dataProvider->getTag(), $operation::getTag() );
					add_action(
						'wp_ajax_' . $moduleKey,
						array( $process, 'run' )
					);
				}
			}
		}
	}

	public function enqueue_scripts() {
		wp_register_style( 'ctci-style', plugins_url( '/admin/css/style.css', __FILE__ ), array(), '0.1', 'all' );
		wp_register_script( 'ctci-scripts', plugins_url( '/admin/js/scripts.js', __FILE__ ), array( 'jquery' ), '0.1' );
		wp_enqueue_style( 'ctci-style' );
		wp_enqueue_script( 'ctci-scripts' );
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
				__( $dataProvider->getHumanReadableName() . ' Settings', self::$TEXT_DOMAIN),
				__( $dataProvider->getHumanReadableName(), self::$TEXT_DOMAIN),
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
		echo '<div id="ctci-run-page-loading-box"></div>';
		foreach ( $this->dataProviders as $dataProvider ) {
			echo '<h3>' . $dataProvider->getHumanReadableName() . '</h3>';
			foreach ( $this->operationTypes as $operation ) {
				if ( $dataProvider->isDataProviderFor( $operation::getTag() ) ) {
					$enabledOpt = $this->get_operation_enabled_option( $dataProvider->getTag(), $operation::getTag() );
					$enabled = false;
					if ( $this->isCTCActive() && isset( $configOptions[ $enabledOpt ] ) && $configOptions[ $enabledOpt ] === 'T' ) {
						$enabled = true;
					}
					echo '<div class="ctci-run-button">';
					switch ( $dataProvider->getRunButtonHandlerType() ) {
						case CTCI_DataProviderInterface::RUNBUTTON_CUSTOM:
							$dataProvider->showSyncButtonFor( $operation, $this->logger, $enabled );
							break;
						default:
						case CTCI_DataProviderInterface::RUNBUTTON_AJAX:
							$this->htmlHelper->showAJAXRunButtonFor( $dataProvider, $operation, $enabled );
							break;
					}
					echo '</div>';
				}
			}
		}
		echo '</div>';
		echo '<div id="ctci-run-messages">';
		echo '<h3>Message Log</h3>';
		echo '<div id="ctci-message-log">';
		echo $this->logger->toHTML();
		echo '</div>';
		echo '</div>';
		echo '</div>';
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
		</div>
	<?php
	}

	public function register_settings() {

		$this->load_global_settings();

		register_setting( self::$CONFIG_GROUP, self::$CONFIG_GROUP, array( $this, 'validate_config_options' ) );
		add_settings_section(
			self::$ENABLE_OPT_SECTION,
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
}

// Instantiate the main class
if ( is_admin() ) { // this if will need to go if adding functionality to anything other than admin
	new Church_Theme_Content_Integration();
}
