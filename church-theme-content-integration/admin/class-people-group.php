<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 5/03/14
 * Time: 10:07 AM
 */

// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

require_once 'interface-people-group.php';

class CTCI_PeopleGroup implements CTCI_PeopleGroupInterface {
	protected $providerTag;
	protected $id;
	protected $name;
	protected $desc;

	public function __construct( $providerTag, $id, $name, $description ) {
		$this->providerTag = $providerTag;
		$this->id = $id;
		$this->name = $name;
		$this->desc = $description;
	}

	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}

	public function id() {
		return $this->id;
	}

	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	public function setDescription( $description ) {
		$this->desc = $description;
		return $this;
	}

	public function getDescription() {
		return $this->desc;
	}

	public function setProviderTag( $tag ) {
		$this->providerTag = $tag;
		return $this;
	}

	public function getProviderTag() {
		return $this->providerTag;
	}
}