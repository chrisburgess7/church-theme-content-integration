<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 27/02/14
 * Time: 2:46 PM
 */

require_once dirname( __FILE__ ) . '/interface-operation.php';

class CTCI_PeopleSync implements CTCI_OperationInterface {

	public static function getTag() {
		return 'people_sync';
	}

	public static function getHumanReadableName() {
		return __( 'People Sync', Church_Theme_Content_Integration::$TEXT_DOMAIN );
	}

	/**
	 * @var CTCI_DataProviderInterface
	 */
	protected $dataProvider;

	/**
	 * @var CTCI_PeopleDataProviderInterface
	 */
	protected $peopleDataProvider;

	/** @var \CTCI_WPALInterface $wpal */
	protected $wpal;

	/** @var CTCI_StatusTrackerInterface $statusTracker */
	protected $statusTracker;

	public function __construct( CTCI_WPALInterface $wpal, CTCI_StatusTrackerInterface $statusTracker ) {
		$this->wpal = $wpal;
		$this->statusTracker = $statusTracker;
	}

	public function setDataProvider( CTCI_DataProviderInterface $dataProvider ) {
		if ( $dataProvider->isDataProviderFor( self::getTag() ) ) {
			$peopleDataProvider = $dataProvider->getDataProviderFor( self::getTag() );
			if ( $peopleDataProvider instanceof CTCI_PeopleDataProviderInterface ) {
				$this->dataProvider = $dataProvider;
				$this->peopleDataProvider = $peopleDataProvider;
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function setStatusTracker( CTCI_StatusTrackerInterface $statusTracker ) {
		$this->statusTracker = $statusTracker;
	}

	public function run() {

		$textDomain = Church_Theme_Content_Integration::$TEXT_DOMAIN;
		$this->statusTracker->info( __( 'Starting People Sync...', $textDomain ), true );

		$dataProvider = $this->peopleDataProvider;
		if ( $dataProvider === null ) {
			throw new LogicException( __( 'Data Provider not set', $textDomain ) );
		}

		$this->statusTracker->info( sprintf( __(
			"Setting up %s for people sync...",
			$textDomain
		), $this->dataProvider->getHumanReadableName() ), true );

		$dataProvider->setupForPeopleSync();

		// update the list of groups if they are being synced
		if ( $dataProvider->syncGroups() ) {
			$this->statusTracker->info( __( 'Updating Groups...', $textDomain ), true );
			$this->updateGroups( $dataProvider );
		}

		// get the people to sync from the data provider
		$people = $dataProvider->getPeople();

		// process the ctc person's that are currently attached
		$attachedCTCPeople = $this->wpal->getCTCPeopleAttachedViaProvider( $dataProvider->getProviderPersonTag() );

		foreach ( $attachedCTCPeople as $ctcPerson ) {
//sleep(3);

			$this->statusTracker->info( sprintf( __( 'Analysing attached person: %s.', $textDomain ), $ctcPerson->getName() ) );

			$dpId = $this->wpal->getAttachedPersonId( $ctcPerson );

			// if the CTC person is attached there should be an associated id no. for the attached record
			// in the data provider. If that is not so, something has gone wrong...
			if ( $dpId === '' ) {
				$this->statusTracker->warning( sprintf( __( 'Person %s could not be synced. Couldn\'t retrieve service provider id.', $textDomain ), $ctcPerson->getName() ) );
				continue;
			}

			if ( isset( $people[ $dpId ] ) ) {
				$this->statusTracker->info( sprintf( __( 'Syncing attached person: %s.', $textDomain ), $ctcPerson->getName() ) );

				$this->syncCTCPerson( $ctcPerson, $people[ $dpId ], $dataProvider->syncGroups() );
				// we've just synced an attached person, so we don't need the record any more
				unset( $people[ $dpId ] );

				$this->statusTracker->addPersonSynced( $ctcPerson->getName() );
			} else {
				// this means that a CTC person with an attached record, no longer has that record in the list
				// of people to sync, so either just delete the attach record, or delete the CTC person entirely
				$this->statusTracker->info( sprintf( __( 'Unattaching person: %s.', $textDomain ), $ctcPerson->getName() ) );

				$this->wpal->unattachCTCPerson( $ctcPerson );
				if ( $dataProvider->deleteUnattachedPeople() ) {
                    $this->statusTracker->info( sprintf( __( 'Deleting person: %s.', $textDomain ), $ctcPerson->getName() ) );

					$this->wpal->deleteCTCPerson( $ctcPerson );

					$this->statusTracker->addPersonDeleted( $ctcPerson->getName() );
				} else {
                    $this->statusTracker->info( sprintf( __( 'Unpublishing person: %s.', $textDomain ), $ctcPerson->getName() ) );

					$this->wpal->unpublishCTCPerson( $ctcPerson );

					$this->statusTracker->addPersonUnattachedUnpublished( $ctcPerson->getName() );
				}
			}
		}

		// now check the unattached people that we now need to sync
		// all attached persons have been removed above
		foreach ( $people as $person ) {
//sleep(3);
			$this->statusTracker->info( sprintf( __( 'Analysing new person: %s.', $textDomain ), $person->getName() ) );

			// attempt to attach the person from the data provider to
			// a person record in wp db
			// this returns the attached ctc person if found, o.w. false
			$attached = $this->attachPerson( $person );

			if ( $attached instanceof CTCI_CTCPersonInterface ) {
				$this->statusTracker->info( sprintf( __( '%s has been attached to an existing person record.', $textDomain ), $person->getName(), $this->dataProvider->getHumanReadableName() ) );

				// make sure that the attached record is published to it shows up
				$this->wpal->publishCTCPerson( $attached );
				$this->syncCTCPerson( $attached, $person, $dataProvider->syncGroups() );

				$this->statusTracker->addPersonAttached( $person->getName() );
			} else {
				$this->statusTracker->info( sprintf( __( 'Creating new record for: %s.', $textDomain ), $person->getName() ) );

				$this->createNewCTCPerson( $person, $dataProvider->syncGroups() );

				$this->statusTracker->addPersonCreated( $person->getName() );
			}
		}

		$dataProvider->cleanUpAfterPeopleSync();

		$this->syncCleanUp();

		return true;
	}

	/**
	 * Update the CTC groups to match any changes from the data provider.
	 * This is needed so that any renaming of groups retains existing information like description.
	 */
	protected function updateGroups( CTCI_PeopleDataProviderInterface $dataProvider ) {
		$groups = $dataProvider->getGroups();

		$attachedCTCGroups = $this->wpal->getCTCGroupsAttachedViaProvider( $dataProvider->getProviderPersonTag() );

		// for all attached CTC groups
		foreach ( $attachedCTCGroups as $ctcGroup ) {
			$dpId = $ctcGroup->getAttachedGroupProviderId();
//sleep(3);
			if ( isset( $groups[ $dpId ] ) ) {
				// sync with attached group
				$this->wpal->updateCTCGroup( $ctcGroup, $groups[ $dpId ] );
				// we are done with this, so remove
				unset( $groups[ $dpId ] );

				$this->statusTracker->addGroupSynced( $ctcGroup->getName() );
			} else {
				// previously attached CTC group no longer has an associated group from the provider
				if ( $dataProvider->deleteUnattachedGroups() ) {
					$this->wpal->deleteCTCGroup( $ctcGroup ); // Note that this also detaches before deleting

					$this->statusTracker->addGroupDeleted( $ctcGroup->getName() );
				} else {
					$this->wpal->unattachCTCGroup( $ctcGroup );

					$this->statusTracker->addGroupUnattached( $ctcGroup->getName() );
				}
			}
		}

		// go through any groups from provider that are new (attached ones removed from groups list above)
		foreach ( $groups as $group ) {
//sleep(3);

			$unattachedCTCGroups = $this->wpal->getUnattachedCTCGroups();
			$attached = false;
			foreach ( $unattachedCTCGroups as $unattachedCTCGroup ) {
				if ( $unattachedCTCGroup->getName() === $group->getName() ) {
					$this->wpal->attachCTCGroup( $unattachedCTCGroup, $group );
					$this->wpal->updateCTCGroup( $unattachedCTCGroup, $group );
					$attached = true;

					$this->statusTracker->addGroupAttached( $group->getName() );
				}
			}

			if ( ! $attached ) {
				$this->wpal->createAttachedCTCGroup( $group );

				$this->statusTracker->addGroupCreated( $group->getName() );
			}
		}
	}

	protected function syncCTCPerson( CTCI_CTCPersonInterface $ctcPerson, CTCI_PersonInterface $person, $syncGroups ) {
		if ( $syncGroups ) {
			$this->wpal->setCTCPersonsGroups( $ctcPerson, $person->getGroups() );
		}
		// perform person sync
		if ( $person->syncName() ) {
			$ctcPerson->editName( $person->getName() );
		}
		if ( $person->syncEmail() ) {
			$ctcPerson->editEmail( $person->getEmail() );
		}
		if ( $person->syncPhone() ) {
			$ctcPerson->editPhone( $person->getPhone() );
		}
		if ( $person->syncPosition() ) {
			$ctcPerson->editPosition( $person->getPosition() );
		}
		if ( $person->syncFacebookURL() ) {
			$ctcPerson->editFacebookURL( $person->getFacebookURL() );
		}
		if ( $person->syncTwitterURL() ) {
			$ctcPerson->editTwitterURL( $person->getTwitterURL() );
		}
		if ( $person->syncLinkedInURL() ) {
			$ctcPerson->editLinkedInURL( $person->getLinkedInURL() );
		}
		$this->wpal->updateCTCPerson( $ctcPerson );
	}

	protected function attachPerson( CTCI_PersonInterface $person ) {
		$unattachedCTCPeople = $this->wpal->getUnattachedCTCPeople();

		foreach ( $unattachedCTCPeople as $unattachedCTCPerson ) {
			// if email is blank?? what's the odds of a name conflict??
			// note this will only match if the name format setting matches the name format in the existing CTC person
			if ( $person->getEmail() === $unattachedCTCPerson->getEmail() &&
				$person->getName() === $unattachedCTCPerson->getName()
			) {
				if ( ! $this->wpal->attachCTCPerson( $unattachedCTCPerson, $person ) ) {
					return false; // specific exception / error handling for this scenario???
				} else {
					return $unattachedCTCPerson;
				}
			}
		}
		return false;
	}

	protected function createNewCTCPerson( CTCI_PersonInterface $person, $syncGroups ) {
		$ctcPerson = $this->wpal->createAttachedCTCPerson( $person );
		if ( $syncGroups ) {
			$this->wpal->setCTCPersonsGroups( $ctcPerson, $person->getGroups() );
		}
	}

	protected function syncCleanUp() {
	}
}
