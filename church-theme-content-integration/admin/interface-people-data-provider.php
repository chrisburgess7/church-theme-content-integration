<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 27/02/14
 * Time: 2:32 PM
 */
interface CTCI_PeopleDataProviderInterface {
	public function getProviderPersonTag();

	public function setupForPeopleSync();

	/**
	 * Must return an associative array of CTCI_PersonInterface's, with the array key being the person id
	 * @return CTCI_PersonInterface[]
	 */
	public function getPeople();

	/**
	 * Return the groups defined in the data provider in the form of an array, where each element is an
	 * array containing 'id' and 'name'.
	 * @return CTCI_PeopleGroupInterface[]
	 */
	public function getGroups();

	/**
	 * Whether or not to sync groups from this data provider.
	 *
	 * @return bool
	 */
	public function syncGroups();

	public function cleanUpAfterPeopleSync();

	/**
	 * Returns whether or not to completely delete any groups that are no longer to be synced. If true, overrides the
	 * default behaviour of simply unpublishing the group.
	 * @return bool
	 */
	public function deleteUnattachedGroups();

	/**
	 * Returns whether or not to completely delete any persons that are no longer to be synced. If true, overrides the
	 * default behaviour of simply unpublishing the person.
	 * @return bool
	 */
	public function deleteUnattachedPeople();
} 