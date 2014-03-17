<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 17/03/14
 * Time: 11:23 AM
 */

require_once dirname(__FILE__) . '/interface-ctc-person.php';

class CTCI_CTCPerson implements CTCI_CTCPersonInterface {

	protected $nameDirty;
	protected $bioDirty;
	protected $positionDirty;
	protected $phoneDirty;
	protected $emailDirty;
	protected $urlsDirty;
	protected $excerptDirty;

	protected $id;
	protected $name;
	protected $bio;
	protected $position;
	protected $phone;
	protected $email;
	protected $urls;
	protected $excerpt;

	public function __construct() {
		$this->nameDirty = false;
		$this->bioDirty = false;
		$this->positionDirty = false;
		$this->phoneDirty = false;
		$this->emailDirty = false;
		$this->urlsDirty = false;
		$this->excerptDirty = false;
	}

	/**
	 * @param mixed $bio
	 * @return $this
	 */
	public function setBio( $bio ) {
		$this->bio = $bio;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBio() {
		return $this->bio;
	}

	/**
	 * @param mixed $email
	 * @return $this
	 */
	public function setEmail( $email ) {
		$this->email = $email;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param mixed $excerpt
	 * @return $this
	 */
	public function setExcerpt( $excerpt ) {
		$this->excerpt = $excerpt;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getExcerpt() {
		return $this->excerpt;
	}

	/**
	 * @param mixed $id
	 * @return $this
	 */
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * @param mixed $name
	 * @return $this
	 */
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $phone
	 * @return $this
	 */
	public function setPhone( $phone ) {
		$this->phone = $phone;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPhone() {
		return $this->phone;
	}

	/**
	 * @param mixed $position
	 * @return $this
	 */
	public function setPosition( $position ) {
		$this->position = $position;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * @param mixed $urls
	 * @return $this
	 */
	public function setUrls( $urls ) {
		$this->urls = $urls;
		return $this;
	}

	public function setURLsFromArray( array $urls ) {
		$this->urls = implode('\n', $urls);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUrls() {
		return $this->urls;
	}

	public function setClean() {
		// TODO: Implement setClean() method.
	}

	public function isNameDirty() {
		// TODO: Implement isNameDirty() method.
	}

	public function isBioDirty() {
		// TODO: Implement isBioDirty() method.
	}

	public function isPositionDirty() {
		// TODO: Implement isPositionDirty() method.
	}

	public function isPhoneDirty() {
		// TODO: Implement isPhoneDirty() method.
	}

	public function isEmailDirty() {
		// TODO: Implement isEmailDirty() method.
	}

	public function isURLsDirty() {
		// TODO: Implement isURLsDirty() method.
	}

	public function isExcerptDirty() {
		// TODO: Implement isExcerptDirty() method.
	}

	public function setGroups( array $ctcGroups ) {
		// TODO: Implement setGroups() method.
	}

	public function getGroups() {
		// TODO: Implement getGroups() method.
	}
}