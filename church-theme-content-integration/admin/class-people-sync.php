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
				$this->updateGroups( $dataProvider );
			}

			// get the people to sync from the data provider
			$people = $dataProvider->getPeople();

			// process the ctc person's that are currently attached
			$attachedCTCPeople = $this->wpal->getCTCPeopleAttachedViaProvider( $dataProvider->getProviderPersonTag() );

			foreach ( $attachedCTCPeople as $ctcPerson ) {
				$dpId = $this->wpal->getAttachedPersonId( $ctcPerson );

				if ( isset( $people[ $dpId ] ) ) {
					$this->syncCTCPerson( $ctcPerson, $people[ $dpId ], $dataProvider->syncGroups() );
					// we've just synced an attached person, so we don't need the record any more
					unset( $people[ $dpId ] );
				} else {
					// this means that a CTC person with an attached record, no longer has that record in the list
					// of people to sync, so either just delete the attach record, or delete the CTC person entirely
					$this->wpal->unattachCTCPerson( $ctcPerson );
					if ( $dataProvider->deleteUnattachedPeople() ) {
						$this->wpal->deleteCTCPerson( $ctcPerson );
					} else {
						$this->wpal->unPublishCTCPerson( $ctcPerson );
					}
				}
			}

			// now check the unattached people that we now need to sync
			// all attached persons have been removed above
			foreach ( $people as $person ) {

				// attempt to attach the person from the data provider to
				// a person record in wp db
				$attached = $this->attachPerson( $person, $dataProvider );

				// no luck, so we create a new person
				if ( !$attached ) {
					$this->createNewCTCPerson( $person );
				}
			}

			$dataProvider->cleanUpAfterPeopleSync();
		}

		$this->syncCleanUp();
	}

	/**
	 * Update the CTC groups to match any changes from the data provider.
	 * This is needed so that any renaming of groups retains existing information like description.
	 */
	protected function updateGroups( CTCI_PeopleDataProviderInterface $dataProvider ) {
		$groups = $dataProvider->getGroups();

		// attached ctc groups not in groups list?
		$attachedCTCGroups = $this->wpal->getCTCGroupsAttachedViaProvider( $dataProvider->getProviderPersonTag() );

		// for all attached CTC groups
		foreach ( $attachedCTCGroups as $ctcGroup ) {
			$dpId = $ctcGroup->getAttachedGroupProviderId();

			if ( isset( $groups[ $dpId ] ) ) {
				// sync with attached group
				$this->wpal->updateCTCGroup( $ctcGroup, $groups[ $dpId ] );
				// we are done with this, so remove
				unset( $groups[ $dpId ] );
			} else {
				// previously attached CTC group no longer has an associated group from the provider
				$this->wpal->unattachCTCGroup( $ctcGroup );
				if ( $dataProvider->deleteUnattachedGroups() ) {
					$this->wpal->deleteCTCGroup( $ctcGroup );
				}
			}
		}

		// go through any groups from provider that are new (attached ones removed above)
		foreach ( $groups as $group ) {

			$unattachedCTCGroups = $this->wpal->getUnattachedCTCGroups();
			$attached = false;
			foreach ( $unattachedCTCGroups as $unattachedCTCGroup ) {
				if ( $unattachedCTCGroup->getName() === $group->getName() ) {
					$this->wpal->attachCTCGroup( $unattachedCTCGroup, $group );
					$this->wpal->updateCTCGroup( $unattachedCTCGroup, $group );
					$attached = true;
				}
			}

			if ( ! $attached ) {
				$this->wpal->createAttachedCTCGroup( $group );
			}
		}
	}

	/*protected function getCTCPerson( $providerTag, $personId ) {
		return false;
	}*/

	protected function syncCTCPerson( CTCI_CTCPersonInterface $ctcPerson, CTCI_PersonInterface $person, $syncGroups ) {
		if ( $syncGroups ) {
			$this->wpal->setCTCPersonsGroups( $ctcPerson, $person->getGroups() );
		}
		// perform person sync
		$this->wpal->updateCTCPerson( $ctcPerson, $person );
	}

	/*protected function syncCTCPersonsGroups( CTCI_CTCPersonInterface $ctcPerson, CTCI_PersonInterface $person ) {
		// replaces all existing terms i.e. groups, with the new ones
		//wp_set_object_terms($ctcPerson->ID, $person->getGroups(), CTCI_Config::$ctcPersonGroupTaxonomy);

	}*/

	protected function attachPerson( CTCI_PersonInterface $person, CTCI_PeopleDataProviderInterface $dataProvider ) {
		$unattachedCTCPeople = $this->wpal->getUnattachedCTCPeople();

		foreach ( $unattachedCTCPeople as $unattachedCTCPerson ) {
			if ( $person->getEmail() === $unattachedCTCPerson->getEmail() &&
				$person->getName( $dataProvider->getNameFormat() ) === $unattachedCTCPerson->getName()
			) {
				$this->wpal->attachCTCPerson( $unattachedCTCPerson, $person );
				$this->wpal->updateCTCPerson( $unattachedCTCPerson, $person );
				return true;
			}
		}
		return false;
	}

	protected function createNewCTCPerson( CTCI_PersonInterface $person ) {
		$this->wpal->createAttachedCTCPerson( $person );
	}

	protected function syncCleanUp() {
	}
} 