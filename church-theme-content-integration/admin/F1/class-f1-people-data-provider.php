<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/02/14
 * Time: 11:59 AM
 */
class CTCI_F1PeopleDataProvider implements CTCI_PeopleDataProviderInterface {

	protected $settings;

	protected $authClient;

	protected $syncGroups = true;
	protected $peopleListsToSync = array();

	public function __construct( CTCI_F1OAuthClientInterface $authClient, CTCI_F1PeopleSyncSettingsInterface $settings ) {
		$this->settings = $settings;
		$this->authClient = $authClient;
		$this->syncGroups = $settings->f1SyncGroups();
		$this->peopleListsToSync = $settings->getF1PeopleLists();
	}

	/**************************************
	 *
	 * People Sync Methods
	 *
	 *************************************/

	protected $people = array();

	protected $groups = array();

	public function getProviderPersonTag() {
		return 'f1';
	}

	public function syncGroups() {
		return $this->syncGroups;
	}

	public function setupForPeopleSync() {
		$this->people = array();
		$this->groups = array();
		$this->retrievePeopleData();
	}

	protected function retrievePeopleData() {

	}

	/**
	 * @return CTCI_PersonInterface[]
	 */
	public function getPeople() {
		return $this->people;
	}

	/**
	 * Return the groups defined in the data provider in the form of an array, where each element is an
	 * array containing 'id' and 'name'.
	 * @return array
	 */
	public function getGroups() {
		return $this->groups;
	}

}