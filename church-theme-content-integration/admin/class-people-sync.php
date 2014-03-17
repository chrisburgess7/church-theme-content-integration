<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 27/02/14
 * Time: 2:46 PM
 */
class CTCI_PeopleSync {
	/**
	 * @var CTCI_PeopleDataProviderInterface[]
	 */
	protected $dataProviders;

	/** @var \CTCI_WPALInterface $wpal */
	protected $wpal;

	public function __construct( CTCI_WPALInterface $wpal, array $dataProviders ) {
		$this->wpal = $wpal;
		$this->dataProviders = $dataProviders;
	}

	public function sync() {

		/** @var $dataProvider CTCI_PeopleDataProviderInterface */
		foreach ( $this->dataProviders as $dataProvider ) {
			$dataProvider->setupForPeopleSync();

			// update the list of groups if they are being synced
			if ( $dataProvider->syncGroups() ) {
				$this->updateGroups();
			}

			// get the people to sync from the data provider
			$people = $dataProvider->getPeople();

			// process the ctc person's that are currently attached
			$attachedCTCPeople = $this->wpal->getCTCPeopleAttachedViaProvider( $dataProvider->getProviderPersonTag() );

			foreach ( $attachedCTCPeople as $ctcPerson ) {
				// search the provider persons list for an attached record

				$dpId = $this->wpal->getAttachedPersonId( $ctcPerson );

				$match = false;
				foreach ( $people as $person ) {
					if ($person->id() == $dpId) {
						// we have the attached record
						$this->syncCTCPerson( $ctcPerson, $person, $dataProvider->syncGroups() );
						$match = true;
						break;
					}
				}
				if ( ! $match ) {
					// this means that a CTC person with an attached record, no longer has that record in the list
					// of people to sync, so either just delete the attach record, or delete the CTC person entirely
					$this->wpal->unattachCTCPerson( $ctcPerson );
					if ( $dataProvider->deleteUnattached() ) {
						$this->wpal->deleteCTCPerson( $ctcPerson );
					}
				} else {
					// we've just synced an attached person, so we don't need the record any more
					unset( $people[$dpId] );
				}
			}

			// now check the unattached people - all attached persons have been removed above
			foreach ( $people as $person ) {

				// attempt to attach the person from the data provider to
				// a person record in wp db
				$attached = $this->attachPerson( $person );

				// no luck, so we create a new person
				if ( !$attached ) {
					$this->createNewCTCPerson( $person );
				}
			}
		}






		$this->syncCleanUp();
	}

	/**
	 * Update the CTC groups to match any changes from the data provider.
	 * This is needed so that any renaming of groups retains existing information like description.
	 */
	protected function updateGroups() {
		$groups = $this->dataProvider->getGroups();

		foreach ( $groups as $group ) {
			// get attached ctc group id from custom table, if it exists

			// if ctc group id exists

			// check if name of that group matches, if not update name

			// else

			// check all unattached ctc groups for a name match, and if found attach
		}
	}

	/**
	 * @param $providerTag
	 * @param $personId
	 * @return WP_Post
	 */
	protected function getCTCPerson( $providerTag, $personId ) {
		return false;
	}

	protected function syncCTCPerson( CTCI_CTCPersonInterface $ctcPerson, CTCI_PersonInterface $person, $syncGroups ) {
		if ( $syncGroups ) {
			$this->syncCTCPersonsGroups( $ctcPerson, $person);
		}
		// perform person sync
	}

	protected function syncCTCPersonsGroups( CTCI_CTCPersonInterface $ctcPerson, CTCI_PersonInterface $person ) {
		// replaces all existing terms i.e. groups, with the new ones
		//wp_set_object_terms($ctcPerson->ID, $person->getGroups(), CTCI_Config::$ctcPersonGroupTaxonomy);
	}

	protected function attachPerson( CTCI_PersonInterface $person ) {
		// should only attach to a ctc person with no attachment to any data provider (not just the current one)
		// this may also need to call $this->syncCTCPerson after attaching
		return false;
	}

	protected function createNewCTCPerson( $person ) {
	}

	protected function syncCleanUp( $data = array() ) {
	}
} 