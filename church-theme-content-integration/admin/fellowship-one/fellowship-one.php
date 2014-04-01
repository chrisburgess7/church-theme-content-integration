<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 26/03/14
 * Time: 1:45 PM
 */

require_once dirname( __FILE__ ) . '/../class-data-provider.php';
require_once dirname( __FILE__ ) . '/interface-f1-api-settings.php';
require_once dirname( __FILE__ ) . '/interface-f1-people-sync-settings.php';
require_once dirname( __FILE__ ) . '/OAuth/class-f1-oauth-client.php';
require_once dirname( __FILE__ ) . '/class-f1-people-data-provider.php';

class CTCI_Fellowship_One extends CTCI_DataProvider implements CTCI_F1APISettingsInterface, CTCI_F1PeopleSyncSettingsInterface {

	protected $configFieldsBaseName = null;
	protected $peopleSyncEnableFieldName;

	/**
	 * @var CTCI_F1OAuthClientInterface
	 */
	protected $authClient = null;
	protected $peopleDataProvider = null;

	/**
	 * @return string   A unique tag for this provider. Should only contain letters, numbers, or underscore.
	 */
	public function getTag() {
		return 'f1';
	}

	/**
	 * @return string   A human readable name for the provider.
	 */
	public function getHumanReadableName() {
		return 'FellowshipOne';
	}

	/**
	 * Return the file paths of php files to be included for this data provider.  The file paths should be relative
	 * to the data provider's root folder.
	 *
	 * @param string $type Either 'always' - return include files that should always be loaded.
	 *                          'admin' - for include files to be loaded in admin pages only (recommended).
	 *                          'frontend' - for files to be included in frontend pages only.
	 * @return array            An array of file names or paths, depending on $type
	 */
	public function getIncludes( $type ) {
		switch ( $type ) {
			case 'admin':
				return array(
					'class-f1-people-data-provider.php',
					//'OAuth/class-f1-api-keys.php', // TODO: leave this??
					'OAuth/interface-f1-oauth-client.php',
					'OAuth/class-f1-api-util.php',
					'OAuth/class-f1-app-config.php',
					'OAuth/class-f1-oauth-client.php',
					'OAuth/class-request-signer.php',
				);
			default:
				return array();
		}
	}

	protected function getSettingsGroupName() {
		return 'ctci_f1_options';
	}

	protected function getSettingsPageName() {
		return 'ctci_f1_options_page';
	}

	protected function registerSectionsAndFields() {
		$this->addSettingsSection(
			'ctci_f1_credentials',
			'Credentials',
			'credentialsSectionCallback'
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'api_key',
			'API Consumer Key',
			'displayTextField',
			array(
				'size' => '6'
			)
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'api_secret',
			'API Consumer Secret',
			'displayTextField',
			array(
				'size' => '40'
			)
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'username',
			'Username',
			'displayTextField'
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'password',
			'Password',
			'displayPasswordField'
		);
		$this->addSettingsSection(
			'ctci_f1_people_sync_settings',
			'People Sync Settings',
			'peopleSyncSectionCallback'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_people_groups',
			'Sync Groups?',
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'people_lists',
			'People Lists to Sync',
			'displayTextAreaField',
			array(
				'rows' => '10',
				'cols' => '30'
			)
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_position',
			'Sync Position?',
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'position_attribute',
			'Position Attribute Group',
			'displayTextField',
			array(
				'maxlength' => '50',
				'size' => '50'
			)
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_phone',
			'Sync Phone?',
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_email',
			'Sync Email?',
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_facebook',
			'Sync Facebook URL?',
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_twitter',
			'Sync Twitter URL?',
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_linkedin',
			'Sync LinkedIn URL?',
			'displayCheckBoxField'
		);
	}

	public function credentialsSectionCallback() {}

	public function peopleSyncSectionCallback() {}

	public function validateSettings( $settings ) {
		$newInput = array();
		$newInput['api_key'] = trim( $settings['api_key'] );
		if ( preg_match( '/\D/', $newInput['api_key'] ) ) {
			$newInput['api_key'] = '';
		}
		$newInput['api_secret'] = trim( $settings['api_secret'] );
		if ( preg_match( '/[^0-9a-f-]/i', $newInput['api_secret'] ) ) {
			$newInput['api_secret'] = '';
		}
		$newInput['username'] = trim( $settings['username'] );
		if ( ! preg_match( '/^[\w]+$/', $newInput['username'] ) ) {
			$newInput['username'] = '';
		}
		$newInput['password'] = trim( $settings['password'] );
		if ( ! preg_match( '/^[\w;:,.\?!@#$%\^&\*\(\)-]+$/', $newInput['password'] ) ) {
			$newInput['password'] = '';
		}
		$newInput['sync_people_groups'] = trim( $settings['sync_people_groups'] );
		if ( 'T' !== $newInput['sync_people_groups'] && 'F' !== $newInput['sync_people_groups'] ) {
			$newInput['sync_people_groups'] = 'F';
		}
		$newInput['people_lists'] = trim( $settings['people_lists'] );
		// is this needed? not sure what else to validate for
		$lines = explode( "\r\n", $newInput['people_lists'] );
		$changed = false;
		for ( $i = 0; $i < count($lines); $i++) {
			if ( strlen( $lines[ $i ] ) > 100 ) {
				$lines[ $i ] = substr( $lines[ $i ], 0, 100 );
				$changed = true;
			}
		}
		if ( $changed ) {
			$newInput['people_lists'] = implode( "\r\n", $lines );
		}
		$newInput['sync_position'] = trim( $settings['sync_position'] );
		if ( 'T' !== $newInput['sync_position'] && 'F' !== $newInput['sync_position'] ) {
			$newInput['sync_position'] = 'F';
		}
		$newInput['position_attribute'] = trim( $settings['position_attribute'] );
		if ( strlen( $newInput['position_attribute'] ) > 50 ) {
			$newInput['position_attribute'] = substr( $newInput['position_attribute'], 0, 50 );
		}
		$newInput['sync_phone'] = trim( $settings['sync_phone'] );
		if ( 'T' !== $newInput['sync_phone'] && 'F' !== $newInput['sync_phone'] ) {
			$newInput['sync_phone'] = 'F';
		}
		$newInput['sync_email'] = trim( $settings['sync_email'] );
		if ( 'T' !== $newInput['sync_email'] && 'F' !== $newInput['sync_email'] ) {
			$newInput['sync_email'] = 'F';
		}
		$newInput['sync_facebook'] = trim( $settings['sync_facebook'] );
		if ( 'T' !== $newInput['sync_facebook'] && 'F' !== $newInput['sync_facebook'] ) {
			$newInput['sync_facebook'] = 'F';
		}
		$newInput['sync_twitter'] = trim( $settings['sync_twitter'] );
		if ( 'T' !== $newInput['sync_twitter'] && 'F' !== $newInput['sync_twitter'] ) {
			$newInput['sync_twitter'] = 'F';
		}
		$newInput['sync_linkedin'] = trim( $settings['sync_linkedin'] );
		if ( 'T' !== $newInput['sync_linkedin'] && 'F' !== $newInput['sync_linkedin'] ) {
			$newInput['sync_linkedin'] = 'F';
		}
		return $newInput;
	}

	public function initDataProvider() {
		$this->authClient = new CTCI_F1OAuthClient( $this );
		$this->peopleDataProvider = new CTCI_F1PeopleDataProvider( $this->authClient, $this );
	}

	public function authenticate() {
		$success = $this->authClient->authenticate();
		if ( ! $success) {
			throw new CTCI_AuthenticationException( 'Could not authenticate.' );
		}
		return $success;
	}

	public function isProviderFor( $operation ) {
		switch ( $operation ) {
			case CTCI_PeopleSync::getTag():
				return true;
			default:
				return false;
		}
	}

	public function getDataProviderFor( $operation ) {
		switch ( $operation ) {
			case CTCI_PeopleSync::getTag():
				return $this->peopleDataProvider;
			default:
				return null;
		}
	}

	/**********************************
	 *
	 * People data provider methods
	 *
	 ***********************************/


	public function getProviderPersonTag() {
		return 'f1';
	}

	public function setupForPeopleSync() {
		// TODO: Implement setupForPeopleSync() method.
	}

	/**
	 * Must return an associative array of CTCI_PersonInterface's, with the array key being the person id
	 * @return CTCI_PersonInterface[]
	 */
	public function getPeople() {
		// TODO: Implement getPeople() method.
	}

	/**
	 * Return the groups defined in the data provider in the form of an array, where each element is an
	 * array containing 'id' and 'name'.
	 * @return CTCI_PeopleGroupInterface[]
	 */
	public function getGroups() {
		// TODO: Implement getGroups() method.
	}

	/**
	 * Whether or not to sync groups from this data provider.
	 *
	 * @return bool
	 */
	public function syncGroups() {
		// TODO: Implement syncGroups() method.
	}

	public function cleanUpAfterPeopleSync() {
		// TODO: Implement cleanUpAfterPeopleSync() method.
	}

	/**
	 * Returns whether or not to completely delete any groups that are no longer to be synced. If true, overrides the
	 * default behaviour of simply unpublishing the group.
	 * @return bool
	 */
	public function deleteUnattachedGroups() {
		// TODO: Implement deleteUnattachedGroups() method.
	}

	/**
	 * Returns whether or not to completely delete any persons that are no longer to be synced. If true, overrides the
	 * default behaviour of simply unpublishing the person.
	 * @return bool
	 */
	public function deleteUnattachedPeople() {
		// TODO: Implement deleteUnattachedPeople() method.
	}

	public function getF1ConsumerKey() {
		// TODO: Implement getF1ConsumerKey() method.
	}

	public function getF1ConsumerSecret() {
		// TODO: Implement getF1ConsumerSecret() method.
	}

	public function getF1Username() {
		// TODO: Implement getF1Username() method.
	}

	public function getF1Password() {
		// TODO: Implement getF1Password() method.
	}

	public function getF1ServerBaseURL() {
		// TODO: Implement getF1ServerBaseURL() method.
	}

	public function getF1PeopleLists() {
		// TODO: Implement getF1PeopleLists() method.
	}

	public function f1SyncGroups() {
		// TODO: Implement f1SyncGroups() method.
	}

	public function f1NameFormat() {
		// TODO: Implement f1NameFormat() method.
	}
}