<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 25/03/14
 * Time: 3:50 PM
 */

interface CTCI_DataProviderInterface {

	/**
	 * @return string   A unique tag for this provider. Should only contain letters, numbers, or underscore.
	 */
	public function getTag();

	/**
	 * @return string   A human readable name for the provider.
	 */
	public function getHumanReadableName();

	/**
	 * Return the file paths of php files to be included for this data provider.  The file paths should be relative
	 * to the data provider's root folder.
	 *
	 * @param string $type     Either 'always' - return include files that should always be loaded.
	 *                          'admin' - for include files to be loaded in admin pages only (recommended).
	 *                          'frontend' - for files to be included in frontend pages only.
	 * @return array            An array of file names or paths, depending on $type
	 */
	public function getIncludes( $type );

	public function isProviderFor( $function );

	public function registerSettings();

	public function validateSettings( $settings );

	public function showSettingsPage();

	/*public function setEnableFieldName( $function, $fieldName );

	public function getEnableFieldName( $function );

	public function addEnableField( $function, $page, $section, $configFieldsBaseName );*/

	//public function showEnablePeopleSyncField();

} 