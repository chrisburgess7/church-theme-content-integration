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
		return 'People Sync';
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

	/** @var CTCI_LoggerInterface $logger */
	protected $logger;

	public function __construct( CTCI_WPALInterface $wpal, CTCI_LoggerInterface $logger ) {
		$this->wpal = $wpal;
		$this->logger = $logger;
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

	public function setLogger( CTCI_LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	public function run() {

		// TODO: think through error handling
		// TODO: internationalisation of messages
		$this->logger->info( 'Starting People Sync...' );

		$dataProvider = $this->peopleDataProvider;
		if ( $dataProvider === null ) {
			throw new LogicException( 'Data Provider not set' );
		}

		$this->logger->info( sprintf( "Setting up %s for people sync...", $this->dataProvider->getHumanReadableName() ) );
		$dataProvider->setupForPeopleSync();

		// update the list of groups if they are being synced
		if ( $dataProvider->syncGroups() ) {
			$this->logger->info( 'Updating Groups...' );
			$this->updateGroups( $dataProvider );
		}

		// get the people to sync from the data provider
		$people = $dataProvider->getPeople();

		// process the ctc person's that are currently attached
		$attachedCTCPeople = $this->wpal->getCTCPeopleAttachedViaProvider( $dataProvider->getProviderPersonTag() );

		foreach ( $attachedCTCPeople as $ctcPerson ) {
			$this->logger->info( sprintf( 'Analysing attached person: %s.', $ctcPerson->getName() ) );
			$dpId = $this->wpal->getAttachedPersonId( $ctcPerson );

			// if the CTC person is attached there should be an associated id no. for the attached record
			// in the data provider. If that is not so, something has gone wrong...
			if ( $dpId === '' ) {
				$this->logger->warning( sprintf( 'Person %s could not be synced. Couldn\'t retrieve service provider id.', $ctcPerson->getName() ) );
				continue;
			}

			if ( isset( $people[ $dpId ] ) ) {
				$this->logger->info( sprintf( 'Syncing attached person: %s.', $ctcPerson->getName() ) );
				$this->syncCTCPerson( $ctcPerson, $people[ $dpId ], $dataProvider->syncGroups() );
				// we've just synced an attached person, so we don't need the record any more
				unset( $people[ $dpId ] );
			} else {
				// this means that a CTC person with an attached record, no longer has that record in the list
				// of people to sync, so either just delete the attach record, or delete the CTC person entirely
				$this->logger->info( sprintf( 'Unattaching person: %s.', $ctcPerson->getName() ) );
				$this->wpal->unattachCTCPerson( $ctcPerson );
				if ( $dataProvider->deleteUnattachedPeople() ) {
					$this->wpal->deleteCTCPerson( $ctcPerson );
				} else {
					$this->wpal->unpublishCTCPerson( $ctcPerson );
				}
			}
		}

		// now check the unattached people that we now need to sync
		// all attached persons have been removed above
		foreach ( $people as $person ) {
			$this->logger->info( sprintf( 'Analysing new person: %s.', $person->getName() ) );

			// attempt to attach the person from the data provider to
			// a person record in wp db
			// this returns the attached ctc person if found, o.w. false
			$attached = $this->attachPerson( $person );

			if ( $attached instanceof CTCI_CTCPersonInterface ) {
				$this->logger->info( sprintf( '%s has been attached to an existing person record.', $person->getName(), $this->dataProvider->getHumanReadableName() ) );
				$this->syncCTCPerson( $attached, $person, $dataProvider->syncGroups() );
				$this->logger->info( sprintf( '%s has been synchronized.', $person->getName() ) );
			} else {
				$this->logger->info( sprintf( 'Creating new record for: %s.', $person->getName() ) );
				$this->createNewCTCPerson( $person, $dataProvider->syncGroups() );
				$this->logger->info( 'Created.' );
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

			if ( isset( $groups[ $dpId ] ) ) {
				// sync with attached group
				$this->wpal->updateCTCGroup( $ctcGroup, $groups[ $dpId ] );
				// we are done with this, so remove
				unset( $groups[ $dpId ] );
			} else {
				// previously attached CTC group no longer has an associated group from the provider
				if ( $dataProvider->deleteUnattachedGroups() ) {
					$this->wpal->deleteCTCGroup( $ctcGroup ); // Note that this also detaches before deleting
				} else {
					$this->wpal->unattachCTCGroup( $ctcGroup );
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
			$ctcPerson->editURL( $person->getFacebookURL() );
		}
		if ( $person->syncTwitterURL() ) {
			$ctcPerson->editURL( $person->getTwitterURL() );
		}
		if ( $person->syncLinkedInURL() ) {
			$ctcPerson->editURL( $person->getLinkedInURL() );
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
					return false; // todo: specific exception / error handling for this scenario???
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

class CTCI_NoProviderIdForAttachedCTCPerson extends Exception {
	protected $ctcPerson;
	public function __construct( CTCI_CTCPersonInterface $ctcPerson, $message = '', $code = 0, $innerException = null ) {
		parent::__construct( $message, $code, $innerException );
		$this->ctcPerson = $ctcPerson;
	}
	public function getCTCPerson() {
		return $this->ctcPerson;
	}
}