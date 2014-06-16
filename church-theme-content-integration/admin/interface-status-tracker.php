<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 12/06/14
 * Time: 10:43 AM
 */

interface CTCI_StatusTrackerInterface {
	public function registerDataProvider( CTCI_DataProviderInterface $provider );
	public function setDataProviderToTrack( CTCI_DataProviderInterface $provider );

	public function clear();

	/**
	 * @return CTCI_LoggerInterface
	 */
	public function getLogger();
	public function includeExceptions( $include = true );
	public function info( $message, $setAsSyncMessage = false );
	public function warning( $message, Exception $exception = null );
	public function error( $message, Exception $exception = null );
	public function success( $message );
	public function setLoggerFilter( $flags );
	public function hasWarnings();
	public function hasErrors();

	/* People Sync methods */
	public function addGroupSynced( $groupName );
	public function addGroupDeleted( $groupName );
	public function addGroupUnattached( $groupName );
	public function addGroupAttached( $groupName );
	public function addGroupCreated( $groupName );
	public function addPersonSynced( $personName );
	public function addPersonDeleted( $personName );
	public function addPersonUnattachedUnpublished( $personName );
	public function addPersonAttachedAndSynced( $personName );
	public function addPersonCreated( $personName );
} 