<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 16/04/14
 * Time: 4:15 PM
 */

interface CTCI_HTTPVariablesManagerInterface {
	public function hasPostVar( $var );
	public function getPostVar( $var );
	public function hasGetVar( $var );
	public function getGetVar( $var );
} 