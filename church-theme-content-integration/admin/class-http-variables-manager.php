<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 16/04/14
 * Time: 4:15 PM
 */

// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

require_once dirname( __FILE__ ) . '/interface-http-variables-manager.php';

class CTCI_HTTPVariablesManager implements CTCI_HTTPVariablesManagerInterface {

	public function hasPostVar( $var ) {
		return isset( $_POST[ $var ] );
	}

	public function getPostVar( $var ) {
		return $_POST[ $var ];
	}

	public function hasGetVar( $var ) {
		return isset( $_GET[ $var ] );
	}

	public function getGetVar( $var ) {
		return $_GET[ $var ];
	}
}