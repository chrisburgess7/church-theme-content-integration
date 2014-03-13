<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 10/03/14
 * Time: 9:58 PM
 */

class WP_Test_UnitTestCaseTest extends WP_UnitTestCase {

	/*public function setUp() {
		parent::setUp();

	}*/

	public function test_plugins_active() {
		$this->assertTrue(is_plugin_active('church-theme-content/church-theme-content.php'));
		$this->assertTrue(is_plugin_active('church-theme-content-integration/church-theme-content-integration.php'));
	}

	public function test_db_setup() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$tableName = CTCI_WPAL::$ctcGroupConnectTable;
		$sql = "SHOW TABLES LIKE '$tableName'";
		//var_dump($sql);
		$this->assertEquals($wpdb->get_var($sql), $tableName);
	}

} 