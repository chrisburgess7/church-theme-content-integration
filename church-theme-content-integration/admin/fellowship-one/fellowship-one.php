<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 26/03/14
 * Time: 1:45 PM
 */

require_once dirname( __FILE__ ) . '/../interface-data-provider.php';

class CTCI_Fellowship_One implements CTCI_DataProviderInterface {

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

	public function setEnableFieldName( $function, $fieldName ) {
		switch ( $function ) {
			case Church_Theme_Content_Integration::$PROVIDER_FUNCTION_PEOPLESYNC:
				$this->peopleSyncEnableFieldName = $fieldName;
				break;
		}
	}

	public function getEnableFieldName( $function ) {
		switch ( $function ) {
			case Church_Theme_Content_Integration::$PROVIDER_FUNCTION_PEOPLESYNC:
				return $this->peopleSyncEnableFieldName;
			default:
				return null;
		}
	}

	public function addEnableField( $function, $page, $section, $configFieldsBaseName ) {
		$this->configFieldsBaseName = $configFieldsBaseName;
		switch ( $function ) {
			case Church_Theme_Content_Integration::$PROVIDER_FUNCTION_PEOPLESYNC:
				add_settings_field(
					$this->peopleSyncEnableFieldName,
					sprintf( __('Enable %s People Sync', Church_Theme_Content_Integration::$TEXT_DOMAIN ), $this->getHumanReadableName() ),
					array( $this, 'showEnablePeopleSyncField' ),
					$page,
					$section
				);
				break;
		}
	}

	public function showEnablePeopleSyncField() {
		if ( null === $this->configFieldsBaseName ) {
			return;     // this has been called before addEnableField, which is incorrect
		}
		$optionValues = get_option( $this->configFieldsBaseName );
		$name = sprintf( "%s[%s]", $this->configFieldsBaseName, $this->peopleSyncEnableFieldName );
		printf("<input type='hidden' name='%s' value='F' />", $name);
		printf(
			"<input id='%s' name='%s' type='checkbox' value='T' %s />",
			$this->peopleSyncEnableFieldName,
			$name,
			checked(
				isset( $optionValues[ $this->peopleSyncEnableFieldName ] ) &&
				$optionValues[ $this->peopleSyncEnableFieldName] === 'T',
				true,
				false
			)
		);
	}
}