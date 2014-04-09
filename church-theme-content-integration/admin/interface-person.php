<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 27/02/14
 * Time: 2:40 PM
 */
interface CTCI_PersonInterface {
	public function setId( $id );

	public function id();

	public function setProviderTag( $tag );
	public function getProviderTag();

	/**
	 * @param string $value
	 * @return CTCI_PersonInterface
	 */
	public function setTitle( $value = '' );

	/**
	 * @param string $value
	 * @return CTCI_PersonInterface
	 */
	public function setNamePrefix( $value = '' );

	/**
	 * @param string $value
	 * @return CTCI_PersonInterface
	 */
	public function setFirstName( $value = '' );

	/**
	 * @param string $value
	 * @return CTCI_PersonInterface
	 */
	public function setGoesByName( $value = '' );

	/**
	 * @param string $value
	 * @return CTCI_PersonInterface
	 */
	public function setMiddleName( $value = '' );

	/**
	 * @param string $value
	 * @return CTCI_PersonInterface
	 */
	public function setLastName( $value = '' );

	/**
	 * @param string $value
	 * @return CTCI_PersonInterface
	 */
	public function setNameSuffix( $value = '' );

	/**
	 * @param string $format
	 * @return self
	 */
	public function setNameFormat( $format = 'F L' );

	public function getName( $format = '' );

	public function setSyncName( $sync = true );

	public function syncName();

	public function setPosition( $value = '' );

	public function getPosition();

	public function setSyncPosition( $sync = true );

	public function syncPosition();

	public function setPhone( $value = '' );

	public function getPhone();

	public function setSyncPhone( $sync = true );

	public function syncPhone();

	public function setEmail( $value = '' );

	public function getEmail();

	public function setSyncEmail( $sync = true );

	public function syncEmail();

	public function setFacebookURL( $value = '' );

	public function getFacebookURL();

	public function setSyncFacebookURL( $sync = true );

	public function syncFacebookURL();

	public function setTwitterURL( $value = '' );

	public function getTwitterURL();

	public function setSyncTwitterURL( $sync = true );

	public function syncTwitterURL();

	public function setLinkedInURL( $value = '' );

	public function getLinkedInURL();

	public function setSyncLinkedInURL( $sync = true );

	public function syncLinkedInURL();

	public function addGroup( CTCI_PeopleGroupInterface $group );

	/**
	 * @return array    An array of CTCI_PeopleGroupInterface for each group that this person belongs to
	 */
	public function getGroups();

	/**
	 * @return array    An array containing all URL fields.
	 */
	public function getURLs();
} 