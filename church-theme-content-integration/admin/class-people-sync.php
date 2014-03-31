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
	 * @var CTCI_PeopleDataProviderInterface
	 */
	protected $dataProvider;

	/** @var \CTCI_WPALInterface $wpal */
	protected $wpal;

	/** @var CTCI_LoggerInterface $logger */
	protected $logger;

	public function __construct( CTCI_WPALInterface $wpal, CTCI_LoggerInterface $logger ) {
		$this->wpal = $wpal;
		$this->logger = $logger;
	}

	public function setDataProvider( CTCI_DataProviderInterface $dataProvider ) {
		if ( $dataProvider->isProviderFor( self::getTag() ) ) {
			$this->dataProvider = $dataProvider->getDataProviderFor( self::getTag() );
			return true;
		} else {
			return false;
		}
	}

	public function setLogger( CTCI_LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	public function run() {

		// TODO: think through error handling
		$this->logger->info( 'Starting People Sync' );

		$dataProvider = $this->dataProvider;
		if ( $dataProvider === null ) {
			return false;
		}

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

			// if the CTC person is attached there should be an associated id no. for the attached record
			// in the data provider. If that is not so, something has gone wrong, so we quit before more
			// damage is potentially done...
			if ( $dpId === '' ) {
				// todo: it would be better if we simply logged this issue, then continued immediately on to
				// the next person
				throw new CTCI_NoProviderIdForAttachedCTCPerson( $ctcPerson );
			}

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
					$this->wpal->unpublishCTCPerson( $ctcPerson );
				}
			}
		}

		// now check the unattached people that we now need to sync
		// all attached persons have been removed above
		foreach ( $people as $person ) {

			// attempt to attach the person from the data provider to
			// a person record in wp db
			// this returns the attached ctc person if found, o.w. false
			$attached = $this->attachPerson( $person );

			if ( $attached instanceof CTCI_CTCPersonInterface ) {
				$this->syncCTCPerson( $attached, $person, $dataProvider->syncGroups() );
			} else {
				$this->createNewCTCPerson( $person, $dataProvider->syncGroups() );
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
				if ( $dataProvider->deleteUnattachedGroups() ) {
					$this->wpal->deleteCTCGroup( $ctcGroup ); // Note that this also unattaches before deleting
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

	/*protected function getCTCPerson( $providerTag, $personId ) {
		return false;
	}*/

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

	/*protected function syncCTCPersonsGroups( CTCI_CTCPersonInterface $ctcPerson, CTCI_PersonInterface $person ) {
		// replaces all existing terms i.e. groups, with the new ones
		//wp_set_object_terms($ctcPerson->ID, $person->getGroups(), CTCI_Config::$ctcPersonGroupTaxonomy);

	}*/

	protected function attachPerson( CTCI_PersonInterface $person ) {
		$unattachedCTCPeople = $this->wpal->getUnattachedCTCPeople();

		foreach ( $unattachedCTCPeople as $unattachedCTCPerson ) {
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