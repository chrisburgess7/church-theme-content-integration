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
	 * @return CTCI_PersonInterface[]
	 */
	public function getPeople();

	/**
	 * Return the groups defined in the data provider in the form of an array, where each element is an
	 * array containing 'id' and 'name'.
	 * @return array
	 */
	public function getGroups();

	/**
	 * Whether or not to sync groups from this data provider.
	 *
	 * @return bool
	 */
	public function syncGroups();
} 