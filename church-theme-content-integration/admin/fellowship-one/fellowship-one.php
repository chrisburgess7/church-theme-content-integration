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

	/** @var CTCI_WPALInterface */
	protected $wpal;

	protected $configFieldsBaseName = null;
	protected $peopleSyncEnableFieldName;

	protected $nameFormatOptions = array();

	/**
	 * @var CTCI_F1OAuthClientInterface
	 */
	protected $authClient = null;

	protected $peopleDataProvider = null;

	/**
	 * F1 API Settings
	 */
	protected $consumerKey;
	protected $consumerSecret;
	protected $username;
	protected $password;
	protected $serverURL;

	/**
	 * People Sync settings
	 */
	protected $peopleLists;
	protected $syncPeopleGroups;
	protected $peopleNameFormat;
	protected $syncPersonPosition;
	protected $personPositionAttribute;
	protected $syncPersonPhone;
	protected $syncPersonEmail;
	protected $syncPersonFacebookURL;
	protected $syncPersonTwitterURL;
	protected $syncPersonLinkedInURL;

	public function __construct() {
		$this->nameFormatOptions = array(
			'T. FQ M L S' => 'Mr. Johnathan "John" Edward Doe II',
			'T. FQ L S' => 'Mr. Johnathan "John" Doe II',
			'T. F M L S' => 'Mr. Johnathan Edward Doe II',
			'T. F J. L S' => 'Mr. Johnathan E. Doe II',
			'T. F L S' => 'Mr. Johnathan Doe II',
			'T. G J. L S' => 'Mr. John E. Doe II',
			'T. G L S' => 'Mr. John Doe II',
			'T. I. L S' => 'Mr. J. Doe II',
			'T. L S' => 'Mr. Doe II',
			'T. L S, G' => 'Mr. Doe II, John',
			'T. L S, F' => 'Mr. Doe II, Johnathan',
			'T. L S, FQ' => 'Mr. Doe II, Johnathan "John"',
			'FQ M L S' => 'Johnathan "John" Edward Doe II',
			'FQ L S' => 'Johnathan "John" Doe II',
			'F M L S' => 'Johnathan Edward Doe II',
			'F J. L S' => 'Johnathan E. Doe II',
			'F L S' => 'Johnathan Doe II',
			'G J. L S' => 'John E. Doe II',
			'G L S' => 'John Doe II',
			'I L S' => 'J. Doe II',
			'G K.' => 'John D.',
			'G' => 'John',
			'K' => 'D',
			'K, G' => 'D, John',
			'L' => 'Doe',
			'L S' => 'Doe II',
			'L S, I.' => 'Doe II, J.',
			'L S, G' => 'Doe II, John',
			'L S, G J.' => 'Doe II, John E.',
			'L S, F' => 'Doe II, Johnathan',
			'L S, F J.' => 'Doe II, Johnathan E.',
			'L S, FQ' => 'Doe II, Johnathan "John"',
			'L S, FQ J.' => 'Doe II, Johnathan "John" E.',
		);
	}

	public function setWPAL( CTCI_WPALInterface $wpal ) {
		$this->wpal = $wpal;
	}

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
					'interface-f1-api-settings.php',
					'interface-f1-people-sync-settings.php',
					'class-f1-people-data-provider.php',
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
			'api_url',
			'API URL',
			'displayTextField',
			array(
				'size' => '40'
			)
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
			'sync_people_groups',
			'Sync Lists to Groups?',
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'name_format',
			'Name Format',
			'displaySelectField',
			array(
				'options' => $this->nameFormatOptions
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
		$newInput['api_url'] = trim( $settings['api_url'] );
		// credit: https://gist.github.com/dperini/729294
		if ( ! preg_match(
			'%^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?$%iu',
			$newInput['api_url']
		) ) {
			$newInput['api_url'] = '';
		}
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
		if ( ! isset( $this->nameFormatOptions[ $settings['name_format'] ] ) ) {
			$newInput['name_format'] = 'F L';
		} else {
			$newInput['name_format'] = trim( $settings['name_format'] );
		}
		$newInput['sync_people_groups'] = trim( $settings['sync_people_groups'] );
		if ( 'T' !== $newInput['sync_people_groups'] && 'F' !== $newInput['sync_people_groups'] ) {
			$newInput['sync_people_groups'] = 'F';
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

	public function initDataProvider( CTCI_LoggerInterface $logger ) {
		if ( $this->wpal !== null ) {
			$options = $this->wpal->getOption( $this->getSettingsGroupName() );
		} else {
			$options = get_option( $this->getSettingsGroupName() );
		}
		if ( false === $options ) {
			throw new Exception( 'Options for ' . $this->getHumanReadableName() . ' could not be retrieved during initialisation' );
		}
		$this->consumerKey = $options['api_key'];
		$this->consumerSecret = $options['api_secret'];
		$this->username = $options['username'];
		$this->password = $options['password'];
		$this->serverURL = $options['api_url'];
		$this->peopleLists = explode( "\r\n", $options['people_lists']);
		$this->syncPeopleGroups = $options['sync_people_groups'];
		$this->peopleNameFormat = $options['name_format'];
		$this->syncPersonPosition = $options['sync_position'];
		$this->personPositionAttribute = $options['position_attribute'];
		$this->syncPersonPhone = $options['sync_phone'];
		$this->syncPersonEmail = $options['sync_email'];
		$this->syncPersonFacebookURL = $options['sync_facebook'];
		$this->syncPersonTwitterURL = $options['sync_twitter'];
		$this->syncPersonLinkedInURL = $options['sync_linkedin'];

		// NOTE: this must come AFTER setting the above, as the auth client relies on retrieving the set values
		// in it's constructor
		$this->authClient = new CTCI_F1OAuthClient( $this );
		$this->peopleDataProvider = new CTCI_F1PeopleDataProvider( $this->authClient, $this, $logger );
	}

	public function authenticate() {
		if ( empty( $this->consumerKey ) ) {
			throw new CTCI_AuthenticationException( 'API Consumer Key setting does not have a value.' );
		}
		if ( empty( $this->consumerSecret ) ) {
			throw new CTCI_AuthenticationException( 'API Consumer Secret setting does not have a value.' );
		}
		if ( empty( $this->username ) ) {
			throw new CTCI_AuthenticationException( 'API Username setting does not have a value.' );
		}
		if ( empty( $this->password ) ) {
			throw new CTCI_AuthenticationException( 'API Password setting does not have a value.' );
		}
		if ( empty( $this->serverURL ) ) {
			throw new CTCI_AuthenticationException( 'API Server setting does not have a value.' );
		}
		$success = $this->authClient->authenticate();
		if ( ! $success) {
			throw new CTCI_AuthenticationException( 'Could not authenticate.' );
			// T/ODO: remove
			// *** only for debugging!!! ***
			/*throw new CTCI_AuthenticationException(
				sprintf( 'Could not authenticate with credentials: %s %s %s %s %s',
					$this->serverURL, $this->consumerKey, $this->consumerSecret, $this->username, $this->password
				)
			);*/
		}
		return $success;
	}

	public function isDataProviderFor( $operation ) {
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

	/******************************************************
	 *
	 * F1APISettings methods
	 *
	 *******************************************************/

	public function getF1ConsumerKey() {
		return $this->consumerKey;
	}

	public function getF1ConsumerSecret() {
		return $this->consumerSecret;
	}

	public function getF1Username() {
		return $this->username;
	}

	public function getF1Password() {
		return $this->password;
	}

	public function getF1ServerBaseURL() {
		return $this->serverURL;
	}

	/******************************************************
	 *
	 * F1PeopleSyncSettings methods
	 *
	 *******************************************************/

	public function getF1PeopleLists() {
		return $this->peopleLists;
	}

	public function f1SyncPeopleGroups() {
		return $this->syncPeopleGroups;
	}

	public function f1NameFormat() {
		return $this->peopleNameFormat;
	}

	public function f1SyncPersonPosition() {
		return $this->syncPersonPosition;
	}

	public function f1PersonPositionAttribute() {
		return $this->personPositionAttribute;
	}

	public function f1SyncPersonPhone() {
		return $this->syncPersonPhone;
	}

	public function f1SyncPersonEmail() {
		return $this->syncPersonEmail;
	}

	public function f1SyncPersonFacebookURL() {
		return $this->syncPersonFacebookURL;
	}

	public function f1SyncPersonTwitterURL() {
		return $this->syncPersonTwitterURL;
	}

	public function f1SyncPersonLinkedInURL() {
		return $this->syncPersonLinkedInURL;
	}
}