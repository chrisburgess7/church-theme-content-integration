<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 12/06/14
 * Time: 10:46 AM
 */

require_once dirname( __FILE__ ) . '/interface-status-tracker.php';

class CTCI_StatusTracker implements CTCI_StatusTrackerInterface {

	/** @var CTCI_WPALInterface */
	protected $wpal;
	/** @var CTCI_LoggerInterface */
	protected $logger;

	/** @var array|CTCI_DataProviderInterface[]  */
	protected $trackedDataProviders = array();
	protected $dataProviderBeingTracked;

	protected $peopleSyncStatus = array();

	protected $hasWarnings;
	protected $hasErrors;

	public function __construct( CTCI_WPALInterface $wpal, CTCI_LoggerInterface $logger ) {
		$this->wpal = $wpal;
		$this->logger = $logger;
		$this->hasWarnings = false;
		$this->hasErrors = false;
		$this->trackedDataProviders = array();
		$this->peopleSyncStatus = array();
	}

	/**
	 * Add a data provider to track. It's a good idea to call this on *all* data providers that need to be tracked
	 * before calling setDataProviderToTrack and tracking any message for them. This informs this class if more than
	 * one data provider needs to be tracked, and if so will tailor the messages to avoid any ambiguity.
	 * @param CTCI_DataProviderInterface $provider
	 * @return $this
	 */
	public function registerDataProvider( CTCI_DataProviderInterface $provider ) {
		$this->trackedDataProviders[ $provider->getTag() ] = $provider;
		return $this;
	}

	/**
	 * Set the data provider that is currently being tracked. It is highly recommended to call registerDataProvider
	 * on *all* data providers first before using this method on any of them. However, this method will not complain
	 * if you don't. This MUST be called before calling any of the message methods.
	 * @param CTCI_DataProviderInterface $provider
	 */
	public function setDataProviderToTrack( CTCI_DataProviderInterface $provider ) {
		if ( ! isset( $this->trackedDataProviders[ $provider->getTag() ] ) ) {
			// we'll just add it if not added already, though this is not ideal behaviour
			$this->trackedDataProviders[ $provider->getTag() ] = $provider;
		}
		$this->dataProviderBeingTracked = $provider;
	}

	public function clear() {
		$this->hasErrors = false;
		$this->hasWarnings = false;
		$this->logger->clear();
		$this->trackedDataProviders = array();
		$this->dataProviderBeingTracked = null;
		$this->peopleSyncStatus = array();
		$this->wpal->clearSyncStatus();
		return $this;
	}

	public function getLogger() {
		return $this->logger;
	}

	public function includeExceptions( $include = true ) {
		$this->logger->includeExceptions( $include );
		return $this;
	}

	public function info( $message, $setAsSyncMessage = false ) {
		if ( $setAsSyncMessage ) {
			$this->wpal->setSyncMessage( $message . ' ' . $this->buildPeopleSyncStatusMessage());
		}
		$this->logger->info( $message );
		return $this;
	}

	public function warning( $message, Exception $exception = null ) {
		$this->hasWarnings = true;
		$this->wpal->addSyncWarning( $message );
		$this->logger->warning( $message, $exception );
		return $this;
	}

	public function error( $message, Exception $exception = null ) {
		$this->hasErrors = true;
		$this->wpal->addSyncError( $message );
		$this->logger->error( $message, $exception );
		return $this;
	}

	public function success( $message ) {
		$this->wpal->setSyncMessage( $message . ' ' . $this->buildPeopleSyncStatusMessage() );
		$this->logger->success( $message );
		return $this;
	}

	public function setLoggerFilter( $flags ) {
		$this->logger->filter( $flags );
		return $this;
	}

	public function hasWarnings() {
		return $this->hasWarnings;
	}

	public function hasErrors() {
		return $this->hasErrors;
	}

	/**************************
	 *
	 * People Sync methods
	 *
	 *************************/

	/**
	 * @param $groupName
	 */
	public function addGroupSynced( $groupName ) {
		$this->updatePeopleSyncStatus(
			'groups_synced',
			$groupName,
			__( 'Group \'%s\' Synced.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addGroupDeleted( $groupName ) {
		$this->updatePeopleSyncStatus(
			'groups_deleted',
			$groupName,
			__( 'Group \'%s\' Deleted.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addGroupUnattached( $groupName ) {
		$this->updatePeopleSyncStatus(
			'groups_unattached',
			$groupName,
			__( 'Group \'%s\' Unattached.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addGroupAttached( $groupName ) {
		$this->updatePeopleSyncStatus(
			'groups_attached',
			$groupName,
			__( 'Group \'%s\' Attached.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addGroupCreated( $groupName ) {
		$this->updatePeopleSyncStatus(
			'groups_created',
			$groupName,
			__( 'Group \'%s\' Created.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addPersonSynced( $personName ) {
		$this->updatePeopleSyncStatus(
			'persons_synced',
			$personName,
			__( 'Person \'%s\' Synced.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addPersonDeleted( $personName ) {
		$this->updatePeopleSyncStatus(
			'persons_deleted',
			$personName,
			__( 'Person \'%s\' Deleted.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addPersonUnattachedUnpublished( $personName ) {
		$this->updatePeopleSyncStatus(
			'persons_unattached',
			$personName,
			__( 'Person \'%s\' Unattached and Unpublished.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addPersonAttached( $personName ) {
		$this->updatePeopleSyncStatus(
			'persons_attached',
			$personName,
			__( 'Person \'%s\' Attached.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	public function addPersonCreated( $personName ) {
		$this->updatePeopleSyncStatus(
			'persons_created',
			$personName,
			__( 'Person \'%s\' Created.', Church_Theme_Content_Integration::$TEXT_DOMAIN )
		);
	}

	protected function updatePeopleSyncStatus( $property, $name, $textMessage ) {
		$dp = $this->getDataProviderBeingTracked();
		$tag = $dp->getTag();
		if ( ! isset( $this->peopleSyncStatus[ $tag ][ $property ] ) ) {
			$this->peopleSyncStatus[ $tag ][ $property ] = 1;
		} else {
			$this->peopleSyncStatus[ $tag ][ $property ]++;
		}
		$this->info( sprintf( $textMessage, $name ) );
		$this->wpal->setSyncMessage( $this->buildPeopleSyncStatusMessage() );
	}

	protected function buildPeopleSyncStatusMessage() {
		/*$dp = $this->getDataProviderBeingTracked();
		$tag = $dp->getTag();*/
		$numDataProviders = count( $this->trackedDataProviders );
		$msg = '';
		foreach ( $this->trackedDataProviders as $tag => $dp ) {
			if ( isset( $this->peopleSyncStatus[ $tag ]['groups_synced'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Group Synced. ',
						'%d Groups Synced. ',
						$this->peopleSyncStatus[ $tag ]['groups_synced'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['groups_synced']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['groups_deleted'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Group Deleted. ',
						'%d Groups Deleted. ',
						$this->peopleSyncStatus[ $tag ]['groups_deleted'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['groups_deleted']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['groups_unattached'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Group Unattached. ',
						'%d Groups Unattached. ',
						$this->peopleSyncStatus[ $tag ]['groups_unattached'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['groups_unattached']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['groups_created'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Group Created. ',
						'%d Groups Created. ',
						$this->peopleSyncStatus[ $tag ]['groups_created'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['groups_created']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['persons_synced'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Person Synced. ',
						'%d People Synced. ',
						$this->peopleSyncStatus[ $tag ]['persons_synced'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['persons_synced']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['persons_deleted'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Person Deleted. ',
						'%d People Deleted. ',
						$this->peopleSyncStatus[ $tag ]['persons_deleted'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['persons_deleted']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['persons_unattached'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Person Unattached and Unpublished. ',
						'%d People Unattached and Unpublished. ',
						$this->peopleSyncStatus[ $tag ]['persons_unattached'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['persons_unattached']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['persons_attached'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Person Attached. ',
						'%d People Attached. ',
						$this->peopleSyncStatus[ $tag ]['persons_attached'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['persons_attached']
				);
			}
			if ( isset( $this->peopleSyncStatus[ $tag ]['persons_created'] ) ) {
				$msg .= sprintf(
					_n(
						'1 Person Created. ',
						'%d People Created. ',
						$this->peopleSyncStatus[ $tag ]['persons_created'],
						Church_Theme_Content_Integration::$TEXT_DOMAIN
					), $this->peopleSyncStatus[ $tag ]['persons_created']
				);
			}

			if ( $msg !== '' ) {
				if ( $numDataProviders > 1 ) {
					$msg = $dp->getHumanReadableName() . ': ' . $msg;
				}
			}
		}

		return $msg;
	}

	protected function getDataProviderBeingTracked() {
		if ( ! ( $this->dataProviderBeingTracked instanceof CTCI_DataProviderInterface ) ) {
			throw new Exception("Data Provider to track not set in Status Tracker");
		}
		return $this->dataProviderBeingTracked;
	}
}