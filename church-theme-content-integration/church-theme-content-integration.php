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
	public static $CONFIG_CAPABILITY = 'ctci_plugin_config';
	public static $SYNC_CAPABILITY = 'ctci_manage_sync';
	
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
	private $modules = array();

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
		add_action( 'plugins_loaded', array( &$this, 'load_modules' ), 1 );

		// Load language file
		//add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ), 1 );
		
		// Set includes
		add_action( 'plugins_loaded', array( &$this, 'set_includes' ), 1 );

		// Load includes
		add_action( 'plugins_loaded', array( &$this, 'load_includes' ), 1 );

		add_action( 'admin_menu', array( &$this, 'build_admin_menu' ) );
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
			if (isset($roles[$key]) && $role->has_cap('manage_options')){
				$role->add_cap(self::$CONFIG_CAPABILITY);
			}
			if (isset($roles[$key]) && $role->has_cap('edit_others_posts')){
				$role->add_cap(self::$SYNC_CAPABILITY);
			}
		}
	}

	public function deactivation() {
		$this->remove_cap();
	} // function deactivation

	// Remove the plugin-specific custom capability
	protected function remove_cap() {
		$roles = get_editable_roles();
		/** @var $role WP_Role */
		foreach ($GLOBALS['wp_roles']->role_objects as $key => $role) {
			if (isset($roles[$key]) && $role->has_cap(self::$CONFIG_CAPABILITY))
				$role->remove_cap(self::$CONFIG_CAPABILITY);
			if (isset($roles[$key]) && $role->has_cap(self::$SYNC_CAPABILITY))
				$role->remove_cap(self::$SYNC_CAPABILITY);
		}
	} // private function remove_cap
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
						$class = "CTCI_$file";
						if ( class_exists( $class ) && in_array( 'CTCI_DataProviderInterface', class_implements( $class ) ) ) {
							$obj = new $class;
							$this->modules[ $file ] = $obj;
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

				self::$ADMIN_DIR . '/class-ctc-group.php',
				self::$ADMIN_DIR . '/class-ctc-person.php',
				self::$ADMIN_DIR . '/class-people-group.php',
				self::$ADMIN_DIR . '/class-people-sync.php',
				self::$ADMIN_DIR . '/class-person.php',
				self::$ADMIN_DIR . '/class-settings-manager.php',
				self::$ADMIN_DIR . '/class-wpal.php',
				self::$ADMIN_DIR . '/interface-ctc-group.php',
				self::$ADMIN_DIR . '/interface-ctc-person.php',
				self::$ADMIN_DIR . '/interface-f1-api-settings.php',
				self::$ADMIN_DIR . '/interface-f1-people-sync-settings.php',
				self::$ADMIN_DIR . '/interface-general-settings.php',
				self::$ADMIN_DIR . '/interface-people-data-provider.php',
				self::$ADMIN_DIR . '/interface-people-group.php',
				self::$ADMIN_DIR . '/interface-person.php',
				self::$ADMIN_DIR . '/interface-wpal.php',

				// f1
				/*self::$ADMIN_DIR_NAME . '/F1/class-f1-people-data-provider.php',
				self::$ADMIN_DIR_NAME . '/F1/OAuth/class-f1-api-util.php',
				self::$ADMIN_DIR_NAME . '/F1/OAuth/class-f1-app-config.php',
				self::$ADMIN_DIR_NAME . '/F1/OAuth/class-f1-oauth-client.php',
				self::$ADMIN_DIR_NAME . '/F1/OAuth/class-request-signer.php',
				self::$ADMIN_DIR_NAME . '/F1/OAuth/interface-f1-oauth-client.php',*/


			),
			// Frontend only

			'frontend' => array (

			),
		);

		// add include files from modules
		foreach ( $this->modules as $folder => $module ) {
			$modIncludesAlways = $module->getIncludes( 'always' );
			foreach ( $modIncludesAlways as $includeFile ) {
				$includes['always'][] = trailingslashit( self::$ADMIN_DIR ) . trailingslashit( $folder ) . $includeFile;
			}
			$modIncludesAdmin = $module->getIncludes( 'admin' );
			foreach ( $modIncludesAdmin as $includeFile ) {
				$includes['admin'][] = trailingslashit( self::$ADMIN_DIR ) . trailingslashit( $folder ) . $includeFile;
			}
			$modIncludesFrontend = $module->getIncludes( 'frontend' );
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

	public function build_admin_menu() {
		add_management_page(
			__('CTC Integration Options'),
			__('CTC Integration'),
			self::$SYNC_CAPABILITY,
			'ctci-main-options',
			array( &$this, 'show_admin_menu' )
		);
	}

	public function show_admin_menu() {
		if ( ! current_user_can( self::$SYNC_CAPABILITY ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<p>Here is where the form would go if I actually had options.</p>';
		echo '</div>';
	}

}

// Instantiate the main class
if ( is_admin() ) { // this if will need to go if adding functionality to anything other than admin
	new Church_Theme_Content_Integration();
}
