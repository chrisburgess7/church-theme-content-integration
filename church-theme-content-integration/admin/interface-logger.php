<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 31/03/14
 * Time: 2:03 PM
 */

interface CTCI_LoggerInterface {
	public function clear();
	public function info( $message );
	public function warning( $message );
	public function error( $message );
	public function success( $message );
	public function filter( $flags );
	public function toHTML();
} 