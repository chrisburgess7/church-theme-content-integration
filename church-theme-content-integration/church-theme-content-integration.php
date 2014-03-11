<?php
/**
 * Plugin Name: Church Theme Content Integration
 * Plugin URI:
 * Description: Provides integration functionality between the Church Theme Content plugin and other church-related service providers.
 * Version: 1.0
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
		//add_action( 'plugins_loaded', array( &$this, 'set_plugin_data' ), 1 );

		// Define constants
		//add_action( 'plugins_loaded', array( &$this, 'define_constants' ), 1 );

		// Load language file
		//add_action( 'plugins_loaded', array( &$this, 'load_textdomain' ), 1 );

		// Set includes
		add_action( 'plugins_loaded', array( &$this, 'set_includes' ), 1 );

		// Load includes
		add_action( 'plugins_loaded', array( &$this, 'load_includes' ), 1 );

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
	public function define_constants() {

		// Plugin details
		define( 'CTCI_VERSION', $this->plugin_data[ 'Version' ] ); // plugin version
		define( 'CTCI_NAME', $this->plugin_data[ 'Name' ] ); // plugin name
		define( 'CTCI_INFO_URL', $this->plugin_data[ 'PluginURI' ] ); // plugin's info page URL
		define( 'CTCI_FILE', __FILE__ ); // plugin's main file path
		define( 'CTCI_DIR', dirname( plugin_basename( CTCI_FILE ) ) ); // plugin's directory
		define( 'CTCI_PATH', untrailingslashit( plugin_dir_path( CTCI_FILE ) ) ); // plugin's directory
		define( 'CTCI_URL', untrailingslashit( plugin_dir_url( CTCI_FILE ) ) ); // plugin's directory URL

		// Directories
		define( 'CTCI_INC_DIR', 'includes' ); // includes directory
		define( 'CTCI_ADMIN_DIR', CTCI_INC_DIR . '/admin' ); // admin directory
		define( 'CTCI_CLASS_DIR', CTCI_INC_DIR . '/classes' ); // classes directory
		define( 'CTCI_LIB_DIR', CTCI_INC_DIR . '/libraries' ); // libraries directory
		define( 'CTCI_CSS_DIR', 'css' ); // stylesheets directory
		define( 'CTCI_JS_DIR', 'js' ); // JavaScript directory
		define( 'CTCI_IMG_DIR', 'images' ); // images directory
		define( 'CTCI_LANG_DIR', 'languages' ); // languages directory

		// CT Meta Box
		if ( !defined( 'CTMB_URL' ) ) { // in case also used in theme or other plugin
			define( 'CTMB_URL', CTCI_URL . '/' . CTCI_LIB_DIR . '/ct-meta-box' ); // for enqueing JS/CSS
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

		$this->includes = apply_filters( 'ctci_includes', array(

			// Frontend or admin
			/*'always' => array(

			),*/

			// Admin only
			'admin' => array(

				CTCI_ADMIN_DIR . '/class-ctc-group.php',
				CTCI_ADMIN_DIR . '/class-people-group.php',
				CTCI_ADMIN_DIR . '/class-person.php',
				CTCI_ADMIN_DIR . '/class-settings-manager.php',
				CTCI_ADMIN_DIR . '/class-wpal.php',
				CTCI_ADMIN_DIR . '/interface-ctc-group.php',
				CTCI_ADMIN_DIR . '/interface-f1-api-settings.php',
				CTCI_ADMIN_DIR . '/interface-f1-people-sync-settings.php',
				CTCI_ADMIN_DIR . '/interface-general-settings.php',
				CTCI_ADMIN_DIR . '/interface-people-data-provider.php',
				CTCI_ADMIN_DIR . '/interface-people-group.php',
				CTCI_ADMIN_DIR . '/interface-person.php',
				CTCI_ADMIN_DIR . '/interface-wpal.php',

				// f1 - todo: make this pluggable for all providers
				CTCI_ADMIN_DIR . '/class-f1-api-util.php',
				CTCI_ADMIN_DIR . '/class-f1-app-config.php',
				CTCI_ADMIN_DIR . '/class-f1-oauth-client.php',
				CTCI_ADMIN_DIR . '/class-request-signer.php',
				CTCI_ADMIN_DIR . '/interface-f1-oauth-client.php',

				// Libraries
				//CTCI_LIB_DIR . '/ct-meta-box/ct-meta-box.php', // see CTMB_URL constant defined above

			),

			// Frontend only
			/*
			'frontend' => array (

			),
			*/

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
					require_once trailingslashit( CTC_PATH ) . $file;
				}

			}

		}

	}

}

// Instantiate the main class
new Church_Theme_Content_Integration();
