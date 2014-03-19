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
	 * Constructor
	 *
	 * Add actions for methods that define constants and load includes.
	 *
	 */
	public function __construct() {
		// Set plugin data
		add_action( 'plugins_loaded', array( &$this, 'set_plugin_data' ), 1 );

		// init variables
		add_action( 'plugins_loaded', array( &$this, 'init_plugin_variables' ), 1 );

		// Load language file
		//add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ), 1 );

		// Set includes
		add_action( 'plugins_loaded', array( &$this, 'set_includes' ), 1 );

		// Load includes
		add_action( 'plugins_loaded', array( &$this, 'load_includes' ), 1 );

		register_activation_hook( __FILE__, array( $this, 'setup_db' ) );
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

		$this->includes = apply_filters( 'ctci_includes', array(

			// Frontend or admin
			/*'always' => array(

			),*/

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

				// f1 - todo: make this pluggable for all providers
				self::$ADMIN_DIR . '/F1/class-f1-people-data-provider.php',
				self::$ADMIN_DIR . '/F1/OAuth/class-f1-api-util.php',
				self::$ADMIN_DIR . '/F1/OAuth/class-f1-app-config.php',
				self::$ADMIN_DIR . '/F1/OAuth/class-f1-oauth-client.php',
				self::$ADMIN_DIR . '/F1/OAuth/class-request-signer.php',
				self::$ADMIN_DIR . '/F1/OAuth/interface-f1-oauth-client.php',

			),

			// Frontend only

			/*'frontend' => array (

			),*/


		) );

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

}

// Instantiate the main class
new Church_Theme_Content_Integration();
