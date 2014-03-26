<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 5/03/14
 * Time: 9:10 AM
 */
interface CTCI_F1OAuthClientInterface {
	public function setUsername( $username );

	public function setPassword( $password );

	public function authenticate();

	/**
	 * @return $this
	 */
	public function json();

	/**
	 * @return $this
	 */
	public function xml();

	public function getPeopleLists();

	public function getPeopleList( $peopleListId );

	public function getPeopleListMembers( $peopleListId );

	public function getPeopleListMember( $peopleListId, $memberId );
} 