<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 17/03/14
 * Time: 10:27 AM
 */

interface CTCI_CTCPersonInterface {
	public function setClean();
	public function setId( $id );
	public function id();
	public function setName( $name );
	public function editName( $name );
	public function isNameDirty();
	public function getName();
	public function setBio( $bio );
	public function editBio( $bio );
	public function isBioDirty();
	public function getBio();
	public function setPosition( $position );
	public function editPosition( $position );
	public function isPositionDirty();
	public function getPosition();
	public function setPhone( $phone );
	public function editPhone( $phone );
	public function isPhoneDirty();
	public function getPhone();
	public function setEmail( $email );
	public function editEmail( $email );
	public function isEmailDirty();
	public function getEmail();
	public function setURLs( $urls );
	public function editURLs( $urls );
	public function isURLsDirty();
	public function setURLsFromArray( array $urls );
	public function editURLsFromArray( array $urls );
	public function editURL( $url );
	public function getURLs();
	public function setExcerpt( $excerpt );
	public function editExcerpt( $excerpt );
	public function isExcerptDirty();
	public function getExcerpt();
	public function setGroups( array $ctcGroups );
	public function getGroups();
} 