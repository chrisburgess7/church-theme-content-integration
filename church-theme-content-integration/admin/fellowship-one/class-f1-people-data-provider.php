<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/02/14
 * Time: 11:59 AM
 */

require_once dirname( __FILE__ ) . '/../interface-people-data-provider.php';
require_once dirname( __FILE__ ) . '/../class-people-group.php';
require_once dirname( __FILE__ ) . '/../class-person.php';

class CTCI_F1PeopleDataProvider implements CTCI_PeopleDataProviderInterface {

	protected $settings;

	protected $authClient;

	protected $statusTracker;

	/*protected $syncGroups = true;
	protected $peopleListsToSync = array();*/

	public function __construct(
		CTCI_F1OAuthClientInterface $authClient,
		CTCI_F1PeopleSyncSettingsInterface $settings,
		CTCI_StatusTrackerInterface $statusTracker
	) {
		$this->settings = $settings;
		$this->authClient = $authClient;
		$this->statusTracker = $statusTracker;
		/*$this->syncGroups = $settings->f1SyncPeopleGroups();
		$this->peopleListsToSync = $settings->getF1PeopleLists();*/
	}

	/**************************************
	 *
	 * People Sync Methods
	 *
	 *************************************/

	/** @var CTCI_PersonInterface[]  */
	protected $people = array();

	/** @var CTCI_PeopleGroupInterface[]  */
	protected $groups = array();

	public function getProviderPersonTag() {
		return 'f1';
	}

	public function syncGroups() {
		return $this->settings->f1SyncPeopleGroups();
	}

	public function setupForPeopleSync() {
		$this->people = array();
		$this->groups = array();
		$this->retrievePeopleData();
	}

	protected function retrievePeopleData() {
		$peopleListsToSync = $this->settings->getF1PeopleLists();
		$this->authClient->json();

		$this->statusTracker->info( __( 'Retrieving People Lists from Fellowship One...', Church_Theme_Content_Integration::$TEXT_DOMAIN ), true );

		$peopleLists = json_decode( $this->authClient->getPeopleLists() );
		if ( null === $peopleLists ) {
			throw new CTCI_JSONDecodeException;
		}

		foreach ( $peopleLists->peopleLists->peopleList as $peopleList ) {
			if ( in_array( $peopleList->name, $peopleListsToSync ) ) {
				// we have a people list to sync
				$currGroup = new CTCI_PeopleGroup(
					$this->getProviderPersonTag(),
					$peopleList->{'@id'},
					$peopleList->name,
					$peopleList->description
				);
				$this->groups[ $peopleList->{'@id'} ] = $currGroup;

				$this->statusTracker->info( sprintf( __(
					'Retrieving from Fellowship One people from list %s...',
					Church_Theme_Content_Integration::$TEXT_DOMAIN
				), $peopleList->name ), true );

				// scan for members
				$members = json_decode( $this->authClient->getPeopleListMembers( $peopleList->{'@id'} ) );
				if ( null === $members ) {
					throw new CTCI_JSONDecodeException;
				}

				$peopleCount = count( $members->members->member );
				$peopleIndex = 0;

				foreach ( $members->members->member as $member ) {

					$peopleIndex++;
					$this->statusTracker->info( sprintf( __(
						'Processing from Fellowship One person %1$d out of %2$d from list %3$s...',
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $peopleIndex, $peopleCount, $peopleList->name ), true );

					// note, there is a member id, and a person id. We want the person id.
					$personId = $member->person->{'@id'};

					// this person might have already been added if they are a member of another
					// people list already processed.
					if ( isset( $this->people[ $personId ] ) ) {
						if ( $this->syncGroups() ) {
							$this->people[ $personId ]->addGroup( $currGroup );
						}
						continue;
					}

					$personData = $this->authClient->getPerson( $personId );
					$personData = json_decode( $personData );
					if ( null === $personData ) {
						throw new CTCI_JSONDecodeException;
					}
					$personData = $personData->person;
					$person = new CTCI_Person( $this->getProviderPersonTag(), $personData->{'@id'} );

					// handle setting of name format, currently this is a global setting applied to
					// every one. Change to individual setting on each person?
					$nameFormat = $this->settings->f1NameFormat();
					if ( ! empty( $nameFormat ) ) {
						$person->setNameFormat( $nameFormat );
					}

					if ( ! empty( $personData->title ) ) {
						$person->setTitle( $personData->title );
					}
					if ( ! empty( $personData->prefix ) ) {
						$person->setNamePrefix( $personData->prefix );
					}
					if ( ! empty( $personData->firstName ) ) {
						$person->setFirstName( $personData->firstName );
					}
					if ( ! empty( $personData->goesByName ) ) {
						$person->setGoesByName( $personData->goesByName );
					}
					if ( ! empty( $personData->middleName ) ) {
						$person->setMiddleName( $personData->middleName );
					}
					if ( ! empty( $personData->lastName ) ) {
						$person->setLastName( $personData->lastName );
					}
					if ( ! empty( $personData->suffix ) ) {
						$person->setNameSuffix( $personData->suffix );
					}

					// position attribute...
					$attributeData = json_decode( $this->authClient->getPersonAttributes( $personId ) );
					if ( null === $attributeData ) {
						throw new CTCI_JSONDecodeException;
					}
					if ( $this->settings->f1SyncPersonPosition() ) {
						$person->setSyncPosition();
						$position = '';
						if ( isset( $attributeData->attributes ) ) {
							foreach ( $attributeData->attributes->attribute as $attribute ) {
								if ( $attribute->attributeGroup->name === $this->settings->f1PersonPositionAttribute() ) {
									if ( $position !== '' ) {
										$position .= ', ';
									}
									$position .= $attribute->attributeGroup->attribute->name;
								}
							}
							if ( $position !== '' ) {
								$person->setPosition( $position );
							}
						}
					}

					// communication details...
					$communicationsData = json_decode( $this->authClient->getPersonCommunications( $personId ) );
					if ( null === $communicationsData ) {
						throw new CTCI_JSONDecodeException;
					}
					if ( $this->settings->f1SyncPersonPhone() ) {
						$person->setSyncPhone();
					}
					if ( $this->settings->f1SyncPersonEmail() ) {
						$person->setSyncEmail();
					}
					if ( $this->settings->f1SyncPersonFacebookURL() ) {
						$person->setSyncFacebookURL();
					}
					if ( $this->settings->f1SyncPersonTwitterURL() ) {
						$person->setSyncTwitterURL();
					}
					if ( $this->settings->f1SyncPersonLinkedInURL() ) {
						$person->setSyncLinkedInURL();
					}
					if ( isset( $communicationsData->communications ) ) {
						foreach ( $communicationsData->communications->communication as $communication ) {
							if ( $this->settings->f1SyncPersonPhone() &&
								$communication->communicationGeneralType === 'Telephone' && 'true' == $communication->preferred
							) {
								$person->setPhone( $communication->communicationValue );
							}
							if ( $this->settings->f1SyncPersonEmail() &&
								$communication->communicationGeneralType === 'Email' && 'true' == $communication->preferred
							) {
								$person->setEmail( $communication->communicationValue );
							}
							if ( $this->settings->f1SyncPersonFacebookURL() &&
								$communication->communicationType->name === 'Facebook'
							) {
								$person->setFacebookURL( $communication->communicationValue );
							}
							if ( $this->settings->f1SyncPersonTwitterURL() &&
								$communication->communicationType->name === 'Twitter'
							) {
								$person->setTwitterURL( $communication->communicationValue );
							}
							if ( $this->settings->f1SyncPersonLinkedInURL() &&
								$communication->communicationType->name === 'Linked-In'
							) {
								$person->setLinkedInURL( $communication->communicationValue );
							}
						}
					}

					// add to current group if syncing groups
					if ( $this->syncGroups() ) {
						$person->addGroup( $currGroup );
					}

					$this->people[ $person->id() ] = $person;
				}
			}
		}
	}

	/**
	 * @return CTCI_PersonInterface[]
	 */
	public function getPeople() {
		return $this->people;
	}

	/**
	 * @return CTCI_PeopleGroupInterface[]
	 */
	public function getGroups() {
		return $this->groups;
	}

	public function cleanUpAfterPeopleSync() {

	}

	public function deleteUnattachedGroups() {
		return true;   // TODO: make option
	}

	public function deleteUnattachedPeople() {
		return false;   // TODO: make option
	}
}

class CTCI_JSONDecodeException extends Exception {}