<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 31/03/14
 * Time: 3:32 PM
 */

require_once dirname( __FILE__ ) . '/interface-logger.php';

class CTCI_Logger implements CTCI_LoggerInterface {

	public static $INFO = 1;
	public static $WARNING = 2;
	public static $ERROR = 4;
	public static $SUCCESS = 8;

	protected $messages = array();

	protected $filter;

	public function __construct() {
		$this->filter = static::$INFO | static::$WARNING | static::$ERROR | static::$SUCCESS;
	}

	public function clear() {
		$this->messages = array();
		return $this;
	}

	public function info( $message ) {
		$this->messages[] = array(
			static::$INFO,
			$message
		);
		return $this;
	}

	public function warning( $message ) {
		$this->messages[] = array(
			static::$WARNING,
			$message
		);
		return $this;
	}

	public function error( $message ) {
		$this->messages[] = array(
			static::$ERROR,
			$message
		);
		return $this;
	}

	public function success( $message ) {
		$this->messages[] = array(
			static::$SUCCESS,
			$message
		);
		return $this;
	}

	public function filter( $flags ) {
		$this->filter = $flags;
		return $this;
	}

	public function toHTML() {
		$output = '';
		foreach ( $this->messages as $message ) {
			$output .= '<p>';
			switch ( $message[0] ) {
				case static::$ERROR:
					if ( $this->filter & static::$ERROR ) {
						$output .= '<span style="color: red">Error:</span> ';
						$output .= $message[1];
					}
					break;
				case static::$WARNING:
					if ( $this->filter & static::$WARNING ) {
						$output .= '<span style="color: orange">Warning:</span> ';
						$output .= $message[1];
					}
					break;
				case static::$INFO:
					if ( $this->filter & static::$INFO ) {
						$output .= '<span style="color: blue">Info:</span> ';
						$output .= $message[1];
					}
					break;
				case static::$SUCCESS:
					if ( $this->filter & static::$SUCCESS ) {
						$output .= '<span style="color: green">Success:</span> ';
						$output .= $message[1];
					}
					break;
			}
			$output .= '</p>';
		}
		return $output;
	}
}