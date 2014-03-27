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
		// todo...
		return $settings;
	}

}