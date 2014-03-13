<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * Edit 'active_plugins' setting below to point to your main plugin file.
 *
 * @package wordpress-plugin-tests
 */

include_once dirname(__FILE__) . '/../../../../../../ChurchThemeContentIntegrationPlugin/tests/copy-files-to-test-env.php';

// Activates this plugin in WordPress so it can be tested.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array(
		'church-theme-content/church-theme-content.php',
		'church-theme-content-integration/church-theme-content-integration.php'
	),
);

echo PHP_EOL . 'Loading WP bootstrap...' . PHP_EOL;

// If the develop repo location is defined (as WP_DEVELOP_DIR), use that
// location. Otherwise, we'll just assume that this plugin is installed in a
// WordPress develop SVN checkout.

if( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
} else {
	require dirname(__FILE__) . '/../../../../../tests/phpunit/includes/bootstrap.php';
}
