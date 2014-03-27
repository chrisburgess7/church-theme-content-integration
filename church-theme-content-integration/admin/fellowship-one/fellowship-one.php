<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 26/03/14
 * Time: 1:45 PM
 */

require_once dirname( __FILE__ ) . '/../class-data-provider.php';

class CTCI_Fellowship_One extends CTCI_DataProvider {

	protected $configFieldsBaseName = null;
	protected $peopleSyncEnableFieldName;

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
					'OAuth/class-f1-api-keys.php', // TODO: leave this??
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

	public function isProviderFor( $function ) {
		switch ( $function ) {
			case Church_Theme_Content_Integration::$PROVIDER_FUNCTION_PEOPLESYNC:
				return true;
			default:
				return false;
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
			'displayTextField'
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
		if ( ! preg_match( '/\d{3}/', $newInput['api_key'] ) ) {
			$newInput['api_key'] = '';
		}
		$newInput['api_secret'] = trim( $settings['api_secret'] );
		if ( ! preg_match( '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $newInput['api_secret'] ) ) {
			$newInput['api_secret'] = '';
		}
		$newInput['username'] = trim( $settings['username'] );
		if ( ! preg_match( '/[\w]+/', $newInput['username'] ) ) {
			$newInput['username'] = '';
		}
		$newInput['password'] = trim( $settings['password'] );
		if ( ! preg_match( '/[\w-;:,.\?!@#$%\^&\*\(\)]{8,}/', $newInput['password'] ) ) {
			$newInput['password'] = '';
		}
		$newInput['sync_people_groups'] = trim( $settings['sync_people_groups'] );
		if ( 'T' !== $newInput['sync_people_groups'] && 'F' !== $newInput['sync_people_groups'] ) {
			$newInput['sync_people_groups'] = '';
		}
		$newInput['people_lists'] = trim( $settings['people_lists'] );
		// is this needed? not sure what else to validate for
		$lines = explode( '\r\n', $newInput['people_lists'] );
		$changed = false;
		for ( $i = 0; $i < count($lines); $i++) {
			if ( strlen( $lines[ $i ] ) > 100 ) {
				$lines[ $i ] = substr( $lines[ $i ], 0, 100 );
				$changed = true;
			}
		}
		if ( $changed ) {
			$newInput['people_lists'] = implode( $lines );
		}
		$newInput['sync_position'] = trim( $settings['sync_position'] );
		if ( 'T' !== $newInput['sync_position'] && 'F' !== $newInput['sync_position'] ) {
			$newInput['sync_position'] = '';
		}
		$newInput['position_attribute'] = trim( $settings['position_attribute'] );
		if ( strlen( $newInput['position_attribute'] ) > 50 ) {
			$newInput['position_attribute'] = substr( $newInput['position_attribute'], 0, 50 );
		}
		$newInput['sync_phone'] = trim( $settings['sync_phone'] );
		if ( 'T' !== $newInput['sync_phone'] && 'F' !== $newInput['sync_phone'] ) {
			$newInput['sync_phone'] = '';
		}
		$newInput['sync_email'] = trim( $settings['sync_email'] );
		if ( 'T' !== $newInput['sync_email'] && 'F' !== $newInput['sync_email'] ) {
			$newInput['sync_email'] = '';
		}
		$newInput['sync_facebook'] = trim( $settings['sync_facebook'] );
		if ( 'T' !== $newInput['sync_facebook'] && 'F' !== $newInput['sync_facebook'] ) {
			$newInput['sync_facebook'] = '';
		}
		$newInput['sync_twitter'] = trim( $settings['sync_twitter'] );
		if ( 'T' !== $newInput['sync_twitter'] && 'F' !== $newInput['sync_twitter'] ) {
			$newInput['sync_twitter'] = '';
		}
		$newInput['sync_linkedin'] = trim( $settings['sync_linkedin'] );
		if ( 'T' !== $newInput['sync_linkedin'] && 'F' !== $newInput['sync_linkedin'] ) {
			$newInput['sync_linkedin'] = '';
		}
		return $newInput;
	}

}