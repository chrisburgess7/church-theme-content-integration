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
	/*public static $PROVIDER_FUNCTION_PEOPLESYNC = 'people_sync';*/

	public static $ENABLE_OPT_DISPLAY_CALLBACK_FUNCPFX = 'enable_opt_display_';

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

	private $operationList = array();

	/**
	 * @var CTCI_WPALInterface
	 */
	private $wpal;

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
		add_action( 'plugins_loaded', array( &$this, 'load_objects' ), 5 );

		// Set up run module actions
		add_action( 'plugins_loaded', array( &$this, 'load_run_actions' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( &$this, 'build_admin_menu' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
		}
	}

	public function activation() {
		$this->setup_db();
		$this->add_capabilities();
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
						// capitalization?
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
				self::$ADMIN_DIR . '/interface-f1-api-settings.php',
				self::$ADMIN_DIR . '/interface-f1-people-sync-settings.php',
				self::$ADMIN_DIR . '/interface-operation.php',
				self::$ADMIN_DIR . '/interface-general-settings.php',
				self::$ADMIN_DIR . '/interface-people-data-provider.php',
				self::$ADMIN_DIR . '/interface-people-group.php',
				self::$ADMIN_DIR . '/interface-person.php',
				self::$ADMIN_DIR . '/interface-wpal.php',
				self::$ADMIN_DIR . '/class-ctc-group.php',
				self::$ADMIN_DIR . '/class-ctc-person.php',
				self::$ADMIN_DIR . '/class-data-provider.php',
				self::$ADMIN_DIR . '/class-module-process.php',
				self::$ADMIN_DIR . '/class-people-group.php',
				self::$ADMIN_DIR . '/class-people-sync.php',
				self::$ADMIN_DIR . '/class-person.php',
				//self::$ADMIN_DIR . '/class-settings-manager.php',
				self::$ADMIN_DIR . '/class-wpal.php',

				// f1
				/*self::$ADMIN_DIR_NAME . '/fellowship-one/class-f1-people-data-provider.php',
				self::$ADMIN_DIR_NAME . '/fellowship-one/OAuth/class-f1-api-util.php',
				self::$ADMIN_DIR_NAME . '/fellowship-one/OAuth/class-f1-app-config.php',
				self::$ADMIN_DIR_NAME . '/fellowship-one/OAuth/class-f1-oauth-client.php',
				self::$ADMIN_DIR_NAME . '/fellowship-one/OAuth/class-request-signer.php',
				self::$ADMIN_DIR_NAME . '/fellowship-one/OAuth/interface-f1-oauth-client.php',*/


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
		$this->wpal = new CTCI_WPAL();
	}

	public function load_run_actions() {
		// todo: add a list of functions to scan here and elsewhere to make it easier to add them later on
		foreach ( $this->dataProviders as $dataProvider ) {
			if ( $dataProvider->isProviderFor( CTCI_PeopleSync::getTag() ) ) {
				$process = new CTCI_ModuleProcess();
				$process->addDataProvider( $dataProvider );
				$peopleSync = new CTCI_PeopleSync( $this->wpal );
				$process->addOperation( $peopleSync );
				$moduleKey = $this->get_run_module_key( $dataProvider->getTag(), CTCI_PeopleSync::getTag() );
				add_action(
					'wp_ajax_' . $moduleKey,
					array( $process, 'run' )
				);
				$this->addModuleToOperationList( $moduleKey, $dataProvider, CTCI_PeopleSync::getHumanReadableName() );
			}
		}
	}

	private function addGlobalOperation( $key, $label ) {
		$this->operationList['global'][] = array(
			'key' => $key,
			'label' => $label
		);
	}

	private function addModuleToOperationList(
		$key,
		CTCI_DataProviderInterface $dataProvider,
		$operationName
	) {
		if ( ! isset( $this->operationList[ $dataProvider->getTag() ] ) ) {
			$this->operationList[ $dataProvider->getTag() ]['provider'] = $dataProvider;
		}
		$this->operationList[ $dataProvider->getTag() ]['modules'][] = array(
			'label' => 'Run ' . $dataProvider->getHumanReadableName() . ' ' . $operationName,
			'key' => $key
		);
	}

	public function build_admin_menu() {
		add_menu_page(
			__('CTC Integration Options', self::$TEXT_DOMAIN),
			__('CTC Integration', self::$TEXT_DOMAIN),
			self::$RUN_SYNC_CAPABILITY,
			'ctci-main-options',
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
		ksort( $this->operationList );
		echo '<div class="wrap">';
		echo '<h2>' . __( 'Church Theme Content Integration', self::$TEXT_DOMAIN ) . '</h2>';
		echo '<div style="display: inline-block; width: 30%; vertical-align: top; margin-top: 10px">';
		if ( isset( $this->operationList['global'] ) ) {
			foreach ( $this->operationList['global'] as $operation ) {
				echo '<div style="display: inline-block">';
				$this->showRunButton(
					$operation['label'],
					$operation['key']
				);
				echo '</div>';
			}
		}
		foreach ( $this->operationList as $providerTag => $providerOperations ) {
			// make sure to ignore the global operations
			if ( 'global' !== $providerTag ) {
				/** @noinspection PhpUndefinedMethodInspection */
				echo '<h3>' . $providerOperations['provider']->getHumanReadableName() . '</h3>';
				foreach ( $providerOperations['modules'] as $moduleInfo ) {
					echo '<div style="display: inline-block">';
					$this->showRunButton(
						$moduleInfo['label'],
						$moduleInfo['key']
					);
					echo '</div>';
				}
			}
		}
		echo '</div>';
		echo '<div style="display: inline-block; width: 68%; margin-top: 10px">';
		echo '<h3>Message Log</h3>';
		echo '<div id="ctci-message-log" style="padding: 5px 10px; min-height: 100px"></div>';
		echo '</div>';
		echo '</div>';
	}

	protected function showRunButton( $label, $key ) {
		echo '<form name="' . $key . '" action="#" method="post" id="' . $key . '">
			<input type="hidden" name="action" value="' . $key . '">
        <input type="submit" name="' . $key . '_submit" id="' . $key . '_submit" class="button button-primary button-large" value="' . $label . '">
        </form>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            var frm = $("#' . $key . '");
            frm.submit(function (ev) {
                $("#ctci-message-log").html("");
                $.ajax({
                    type: frm.attr("method"),
                    url: ajaxurl,
                    data: frm.serialize(),
                    success: function (data) {
                        $("#ctci-message-log").html(data);
                    }
                });

                ev.preventDefault();
            });
        });
        </script>
    ';
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
		register_setting( self::$CONFIG_GROUP, self::$CONFIG_GROUP, array( $this, 'enable_options_validate' ) );
		add_settings_section(
			self::$ENABLE_OPT_SECTION,
			__( 'Enable Modules', self::$TEXT_DOMAIN ),
			array( $this, 'show_mod_enable_text'),
			self::$ENABLE_OPT_PAGE
		);

		// for each data provider, create an enable button for each function it supports
		foreach ( $this->dataProviders as $dataProvider ) {
			// add more of these conditions for each function if added later
			if ( $dataProvider->isProviderFor( CTCI_PeopleSync::getTag() ) ) {
				$fieldName = $this->get_operation_enabled_option( $dataProvider->getTag(), CTCI_PeopleSync::getTag() );
				add_settings_field(
					$fieldName,
					__( sprintf('Enable %s People Sync', $dataProvider->getHumanReadableName() ), self::$TEXT_DOMAIN ),
					array( $this, 'show_module_enable_field' ),
					self::$ENABLE_OPT_PAGE,
					self::$ENABLE_OPT_SECTION,
					array(
						'fieldName' => $fieldName
					)
				);
			}

			$dataProvider->registerSettings();
		}
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

	public function enable_options_validate( $settings ) {
		$newSettings = array();
		foreach ( $this->dataProviders as $dataProvider ) {
			// add more of these conditions for each function if added later
			if ( $dataProvider->isProviderFor( CTCI_PeopleSync::getTag() ) ) {
				$fieldName = $this->get_operation_enabled_option( $dataProvider->getTag(), CTCI_PeopleSync::getTag() );
				$newSettings[ $fieldName ] = trim( $settings[ $fieldName ] );
				if ( 'T' !== $newSettings[ $fieldName ] && 'F' !== $newSettings[ $fieldName ] ) {
					$newSettings[ $fieldName ] = 'F';
				}
			}
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

	private function get_run_module_key( $providerTag, $operation ) {
		return "ctci_run_{$providerTag}_$operation";
	}
}

// Instantiate the main class
if ( is_admin() ) { // this if will need to go if adding functionality to anything other than admin
	new Church_Theme_Content_Integration();
}
