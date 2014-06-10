<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 31/03/14
 * Time: 2:03 PM
 */

interface CTCI_LoggerInterface {
	public function clear();
	public function includeExceptions( $include = true );
	public function info( $message );
	public function warning( $message, Exception $exception = null );
	public function error( $message, Exception $exception = null );
	public function success( $message );
	public function filter( $flags );
	public function toHTML();
	public function toString();
	public function hasWarnings();
	public function hasErrors();
} 