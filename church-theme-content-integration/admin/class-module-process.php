<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/03/14
 * Time: 2:27 PM
 */

// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTCI_ModuleProcess {
	/** @var CTCI_DataProviderInterface[] $dataProviders */
	protected $dataProviders = array();
	/** @var CTCI_OperationInterface[]  */
	protected $operations = array();

	/** @var CTCI_LoggerInterface /
	protected $statusTracker;*/

	/** @var CTCI_StatusTrackerInterface */
	protected $statusTracker;

	/** @var CTCI_WPALInterface */
	protected $wpal;

	public function __construct( CTCI_StatusTrackerInterface $statusTracker, CTCI_WPAL $wpal ) {
		$this->statusTracker = $statusTracker;
		$this->wpal = $wpal;
	}

	public function addDataProvider( CTCI_DataProviderInterface $dataProvider ) {
		$this->dataProviders[] = $dataProvider;
		return $this;
	}

	public function addOperation( CTCI_OperationInterface $operation ) {
		$this->operations[] = $operation;
		return $this;
	}

	public function run() {

		$textDomain = Church_Theme_Content_Integration::$TEXT_DOMAIN;

		$this->statusTracker->clear();

		foreach ( $this->dataProviders as $dataProvider ) {
			$this->statusTracker->registerDataProvider( $dataProvider );
		}

		foreach ( $this->dataProviders as $dataProvider ) {

			$this->statusTracker->setDataProviderToTrack( $dataProvider );

			try {
				if ( ! $dataProvider->initDataProviderForProcess( $this->statusTracker ) ) {
					$this->statusTracker->error(
						sprintf( __( 'Init failed for provider %s.', $textDomain ), $dataProvider->getHumanReadableName() )
					);
					continue;
				}
			} catch ( Exception $e ) {
				$this->statusTracker->error(
					sprintf(
						__( 'Init failed for provider %s. %s', $textDomain ),
						$dataProvider->getHumanReadableName(),
						$e->getMessage()
					), $e
				);
				continue;
			}

			try {
                // returns boolean true for success, or string if error
                $result = $dataProvider->authenticateForProcess();
				if ( $result !== true ) {
					$this->statusTracker->error(
						sprintf(
							__( 'Authentication failed for provider %s. %s', $textDomain),
							$dataProvider->getHumanReadableName(), $result
						)
					);
					continue;
				}
			} catch ( Exception $e ) {
				$this->statusTracker->error(
					sprintf(
						__( 'Authentication failed for provider %s. %s', $textDomain),
						$dataProvider->getHumanReadableName(),
						$e->getMessage()
					), $e
				);
				continue;
			}

			// run each given operation on this data provider
			foreach ( $this->operations as $operation ) {
				try {
					$valid = $operation->setDataProvider( $dataProvider );
					if ( $valid ) {

						/**
						 * The main point of it all.
						 */
						$operation->run();

						if ( ! $this->statusTracker->hasErrors() ) {
							$procName = $dataProvider->getHumanReadableName() . ' ' . $operation->getHumanReadableName();
							if ( ! $this->statusTracker->hasWarnings() ) {
								$this->statusTracker->success( sprintf( __( '%s complete.', $textDomain ), $procName) );
							} else {
								$this->statusTracker->warning( sprintf( __( '%s has finished with warnings.', $textDomain ), $procName) );
							}
						}
					}
				} catch ( Exception $e ) {
					$this->statusTracker->error(
						sprintf( '%s %s - %s', $dataProvider->getHumanReadableName(),
							$operation->getHumanReadableName(), $e->getMessage()
						), $e
					);
					// debugging...
/*					$this->statusTracker->error(
						sprintf( '%s %s - Type: %s, Message: %s, File: %s, Line: %s', $dataProvider->getHumanReadableName(),
							$operation->getHumanReadableName(), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()
						)
					);*/
				}
			}
		}
	}

	public function runAJAX() {
		$runtime = new DateTime();

		// set 5 minute time limit, hopefully long enough...
		@set_time_limit(300);

		$this->run();

		$logger = $this->statusTracker->getLogger();

		// build some strings identifying this run with it's operations and providers
		$operationString = "Operation(s): ";
		foreach( $this->operations as $op ) {
			$operationString .= $op->getHumanReadableName() . ', ';
		}
		$operationString = substr( $operationString, 0, strlen( $operationString ) - 2 );
		$providersString = "Data provider(s): ";
		foreach( $this->dataProviders as $dp ) {
			$providersString .= $dp->getHumanReadableName() . ', ';
		}
		$providersString = substr( $providersString, 0, strlen( $providersString ) - 2 );

		// output txt file
		$fHandleTxt = fopen( Church_Theme_Content_Integration::getLogFileName(), 'w' );
		if ( $fHandleTxt !== false ) {
			fwrite( $fHandleTxt, "Run at: " . $runtime->format('Y-m-d H:i:s') . PHP_EOL );
			fwrite( $fHandleTxt, $operationString . PHP_EOL);
			fwrite( $fHandleTxt, $providersString . PHP_EOL);
			fwrite( $fHandleTxt, $logger->toString() );
			fclose( $fHandleTxt );
		}

		// output html file for the admin page log viewer
		$fHandleHTML = fopen( Church_Theme_Content_Integration::getLogFileName('html'), 'w' );
		if ( $fHandleHTML !== false ) {
			fwrite( $fHandleHTML, "<p>Run at: " . $runtime->format('Y-m-d H:i:s') . '</p>' );
			fwrite( $fHandleHTML, '<p>' . $operationString . '</p>');
			fwrite( $fHandleHTML, '<p>' . $providersString . '</p>');
			fwrite( $fHandleHTML, $logger->toHTML() );
			fclose( $fHandleHTML );
		}

		// ajax output
		$json = $this->wpal->getSyncStatusAsJSON();
		if ( $json === false ) {
			echo json_encode( array(
				'warnings' => 1,
				'warning_message' => __(
					'An error has occurred retrieving status upon completion. Checking the log may tell you if it worked.',
					Church_Theme_Content_Integration::$TEXT_DOMAIN
				)
			));
		} else {
			echo $json;
		}
		die(); // needed to avoid wordpress returning a zero at the end of the response
	}
} 