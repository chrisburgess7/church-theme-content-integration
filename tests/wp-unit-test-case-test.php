<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 10/03/14
 * Time: 9:58 PM
 */
class WP_Test_UnitTestCaseTest extends WP_UnitTestCase {
	// TODO NEXT write a test to check the WP unit testing setup, with loading CTC specifically
	// unit tests will need a start task that copies all plugin files and test files to
	// the dev wordpress folder, and then run the test from there. The file loading script in
	// this plugin's main file ought to handle any dependencies

	public function test_db_setup() {
		$tableName = CTCI_WPAL::$ctcGroupConnectTable;
		$result = mysql_query("SHOW TABLES LIKE '$tableName'");
		$this->assertGreaterThan(0, mysql_num_rows($result));
	}

} 