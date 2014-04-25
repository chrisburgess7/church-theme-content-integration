<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 25/03/14
 * Time: 3:50 PM
 */

require_once dirname( __FILE__ ) . '/interface-logger.php';
require_once dirname( __FILE__ ) . '/interface-operation.php';
require_once dirname( __FILE__ ) . '/interface-http-variables-manager.php';
require_once dirname( __FILE__ ) . '/interface-html-helper.php';
require_once dirname( __FILE__ ) . '/class-session.php';

interface CTCI_DataProviderInterface {

	const RUNBUTTON_AJAX = 0;
	const RUNBUTTON_CUSTOM = 1;

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

	public function initOnLoad(
		CTCI_Session $session, CTCI_HTTPVariablesManagerInterface $httpVarManager, CTCI_HtmlHelperInterface $htmlHelper
	);

	public function registerSettings();

	public function validateSettings( $settings );

	public function showSettingsPage();

	public function getRunButtonHandlerType();

	public function showSyncButtonFor( CTCI_OperationInterface $operation, CTCI_LoggerInterface $logger );

	/**
	 * Initialise the data provider at the start of any sync process.
	 */
	public function initDataProviderForProcess( CTCI_LoggerInterface $logger );

	public function authenticateForProcess();

	public function isDataProviderFor( $operation );

	/**
	 * When each operation is running, it calls this method to retrieve the data provider for the specific operation.
	 * Each operation has a corresponding interface that the returned object must implement. Gets called after
	 * initDataProviderForProcess.
	 *
	 * @param $operation
	 * @return mixed
	 */
	public function getDataProviderFor( $operation );

	/*public function setEnableFieldName( $function, $fieldName );

	public function getEnableFieldName( $function );

	public function addEnableField( $function, $page, $section, $configFieldsBaseName );*/

	//public function showEnablePeopleSyncField();

}

class CTCI_AuthenticationException extends Exception {}