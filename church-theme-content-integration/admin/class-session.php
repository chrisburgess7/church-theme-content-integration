<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 16/04/14
 * Time: 12:20 PM
 */

interface CTCI_SessionAdapter {
	public function start();
	public function has($var);
	public function get($var);
	public function set($var, $value);
}

class CTCI_Session {
	protected $adapter;
	public function __construct(CTCI_SessionAdapter $adapter)
	{
		$this->adapter = $adapter;
	}
	public function start() {
		$this->adapter->start();
	}
	public function has( $var ) {
		return $this->adapter->has( $var );
	}
	public function get($var)
	{
		return $this->adapter->get($var);
	}
	public function set($var, $value)
	{
		return $this->adapter->set($var, $value);
	}
}

class CTCI_CTCISession extends CTCI_Session {
    public function setF1AccessToken( $value ) {
        $this->adapter->set( 'ctci_f1_access_token', $value );
        return $this;
    }
    public function hasF1AccessToken() {
        return $this->adapter->has('ctci_f1_access_token');
    }
    public function getF1AccessToken() {
        return $this->adapter->get('ctci_f1_access_token');
    }
    public function setF1AccessTokenSecret( $value ) {
        $this->adapter->set( 'ctci_f1_access_token_secret', $value );
        return $this;
    }
    public function hasF1AccessTokenSecret() {
        return $this->adapter->has('ctci_f1_access_token_secret');
    }
    public function getF1AccessTokenSecret() {
        return $this->adapter->get('ctci_f1_access_token_secret');
    }
}

class CTCI_PhpSessionAdapter implements CTCI_SessionAdapter
{
	public function start() {
		if ( ! session_id() ) {
            session_start();
        }
	}
	public function has($var) {
		return isset( $_SESSION[$var] );
	}
	public function get($var)
	{
		return isset($_SESSION[$var]) ? $_SESSION[$var] : null;
	}
	public function set($var, $value)
	{
		$_SESSION[$var] = $value;
	}
}

class CTCI_MemorySessionAdapter implements CTCI_SessionAdapter
{
	private $session = array();

	public function start() {

	}
	public function has($var) {
		return isset( $this->session[ $var ] );
	}
	public function get($var)
	{
		return isset($this->session[$var]) ? $this->session[$var] : null;
	}
	public function set($var, $value)
	{
		$this->session[$var] = $value;
	}
}