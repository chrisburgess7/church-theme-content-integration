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

	protected $logger;

	/*protected $syncGroups = true;
	protected $peopleListsToSync = array();*/

	public function __construct(
		CTCI_F1OAuthClientInterface $authClient,
		CTCI_F1PeopleSyncSettingsInterface $settings,
		CTCI_LoggerInterface $logger
	) {
		$this->settings = $settings;
		$this->authClient = $authClient;
		$this->logger = $logger;
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
		// TODO: look at handling provider errors more closely
		$peopleListsToSync = $this->settings->getF1PeopleLists();
		// maintain an internal list of groups here, indexed by name for easy list searching
		//$groupList = array();
		$this->authClient->json();
		$peopleLists = json_decode( $this->authClient->getPeopleLists() );
		if ( null === $peopleLists ) {
			throw new CTCI_JSONDecodeException;
		}
		foreach ( $peopleLists->peopleLists->peopleList as $peopleList ) {
			if ( in_array( $peopleList->name, $peopleListsToSync ) ) {
				$this->logger->info( 'Processing List: ' . $peopleList->name );

				// we have a people list to sync
				$currGroup = new CTCI_PeopleGroup(
					$this->getProviderPersonTag(),
					$peopleList->{'@id'},
					$peopleList->name,
					$peopleList->description
				);
				$this->groups[ $peopleList->{'@id'} ] = $currGroup;
					// scan for members
				$members = json_decode( $this->authClient->getPeopleListMembers( $peopleList->{'@id'} ) );
				if ( null === $members ) {
					throw new CTCI_JSONDecodeException;
				}
				foreach ( $members->members->member as $member ) {
					// note, there is a member id, and a person id. We want the person id.
					$personId = $member->person->{'@id'};
					if ( isset( $this->people[ $personId ] ) ) {
						// this person might have already been added if they are a member of another
						// people list already processed.
						$this->people[ $personId ]->addGroup( $currGroup );
						continue;
					}
					$personData = $this->authClient->getPerson( $personId );
					$personData = json_decode( $personData );
					if ( null === $personData ) {
						throw new CTCI_JSONDecodeException;
					}
					$personData = $personData->person;
					$person = new CTCI_Person( $this->getProviderPersonTag(), $personData->{'@id'} );
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
					$attributeData = $this->authClient->getPersonAttributes( $personId );
					$attributeData = json_decode( $attributeData );
					if ( null === $attributeData ) {
						throw new CTCI_JSONDecodeException;
					}
					$position = '';
					foreach ( $attributeData->attributes->attribute as $attribute ) {
						if ( $this->settings->f1SyncPersonPosition() &&
							$attribute->attributeGroup->name === $this->settings->f1PersonPositionAttribute()
						) {
							$person->setSyncPosition();
							if ( $position !== '' ) {
								$position .= ', ';
							}
							$position .= $attribute->attributeGroup->attribute->name;
						}
					}
					if ( $position !== '' ) {
						$person->setPosition( $position );
					}

					// communication details...
					$communicationsData = $this->authClient->getPersonCommunications( $personId );
					$communicationsData = json_decode( $communicationsData );
					if ( null === $communicationsData ) {
						throw new CTCI_JSONDecodeException;
					}
					foreach ( $communicationsData->communications->communication as $communication ) {
						if ( $this->settings->f1SyncPersonPhone() &&
							$communication->communicationGeneralType === 'Telephone' && 'true' == $communication->preferred
						) {
							$person->setSyncPhone();
							$person->setPhone( $communication->communicationValue );
						}
						if ( $this->settings->f1SyncPersonEmail() &&
							$communication->communicationGeneralType === 'Email' && 'true' == $communication->preferred
						) {
							$person->setSyncEmail();
							$person->setEmail( $communication->communicationValue );
						}
						if ( $this->settings->f1SyncPersonFacebookURL() &&
							$communication->communicationType->name === 'Facebook'
						) {
							$person->setSyncFacebookURL();
							$person->setFacebookURL( $communication->communicationValue );
						}
						if ( $this->settings->f1SyncPersonTwitterURL() &&
							$communication->communicationType->name === 'Twitter'
						) {
							$person->setSyncTwitterURL();
							$person->setTwitterURL( $communication->communicationValue );
						}
						if ( $this->settings->f1SyncPersonLinkedInURL() &&
							$communication->communicationType->name === 'Linkedin'
						) {
							$person->setSyncLinkedInURL();
							$person->setLinkedInURL( $communication->communicationValue );
						}
					}

					// add to current group
					$person->addGroup( $currGroup );
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
	 * Return the groups defined in the data provider in the form of an array, where each element is an
	 * array containing 'id' and 'name'.
	 * @return CTCI_PeopleGroupInterface[]
	 */
	public function getGroups() {
		return $this->groups;
	}

	public function cleanUpAfterPeopleSync() {

	}

	public function deleteUnattachedGroups() {
		return false;   // TODO: make option
	}

	public function deleteUnattachedPeople() {
		return false;   // TODO: make option
	}
}

class CTCI_JSONDecodeException extends Exception {}