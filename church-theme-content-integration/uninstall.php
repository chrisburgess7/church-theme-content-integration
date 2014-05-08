<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Church Theme Content Integration
 * @author    Chris Burgess
 * @license   GPL-2.0+
 * @link
 * @copyright 2014 Chris Burgess
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// see https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28668017

if (is_multisite()) {
	/** @var $wpdb wpdb */
	global $wpdb;
	$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
	if ($blogs) {
		foreach($blogs as $blog) {
			switch_to_blog($blog['blog_id']);

			uninstall();

			restore_current_blog();
		}
	}
}
else
{
	uninstall();
}

function uninstall() {

	/** @var $wpdb wpdb */
	$wpdb = $GLOBALS['wpdb'];
	$tableName = $wpdb->prefix . 'ctci_ctcgroup_connect';
	$wpdb->query("DROP TABLE IF EXISTS $tableName");
	delete_option('ctci_db_version');
	delete_option('ctci_config_options');
	delete_option('ctci_f1_options');

	// delete all post meta with the plugin's prefix
	$wpdb->query("DELETE FROM " . $wpdb->prefix . "postmeta WHERE SUBSTR(meta_key, 1, 6) = '_ctci_'");
}