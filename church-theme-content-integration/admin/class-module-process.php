<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/03/14
 * Time: 2:27 PM
 */

class CTCI_ModuleProcess {
	/** @var CTCI_DataProviderInterface[] $dataProviders */
	protected $dataProviders = array();
	/** @var CTCI_OperationInterface[]  */
	protected $operations = array();

	/** @var CTCI_LoggerInterface */
	protected $logger;

	/** @var CTCI_WPALInterface */
	protected $wpal;

	public function __construct( CTCI_LoggerInterface $logger, CTCI_WPAL $wpal ) {
		$this->logger = $logger;
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

		$this->logger->clear();

		// set 5 minute time limit, hopefully long enough...
		set_time_limit(300);

		foreach ( $this->dataProviders as $dataProvider ) {
			try {
				if ( ! $dataProvider->initDataProviderForProcess( $this->logger ) ) {
					$this->logger->error(
						sprintf( __( 'Init failed for provider %s.', $textDomain ), $dataProvider->getHumanReadableName() )
					);
					continue;
				}
			} catch ( Exception $e ) {
				$this->logger->error(
					sprintf(
						__( 'Init failed for provider %s. %s', $textDomain ),
						$dataProvider->getHumanReadableName(),
						$e->getMessage()
					), $e
				);
				continue;
			}
			try {
				if ( ! $dataProvider->authenticateForProcess() ) {
					$this->logger->error(
						sprintf(
							__( 'Authentication failed for provider %s.', $textDomain),
							$dataProvider->getHumanReadableName()
						)
					);
					continue;
				}
			} catch ( Exception $e ) {
				$this->logger->error(
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

						if ( ! $this->logger->hasErrors() ) {
							$procName = $dataProvider->getHumanReadableName() . ' ' . $operation->getHumanReadableName();
							if ( ! $this->logger->hasWarnings() ) {
								// todo: a class to encapsulate the sync status and log messages to enforce consistency
								$this->wpal->setSyncMessage( sprintf( __( '%s complete.', $textDomain ), $procName) );
								$this->logger->success( sprintf( __( '%s complete.', $textDomain ), $procName) );
							} else {
								$this->logger->warning( sprintf( __( '%s has finished with warnings.', $textDomain ), $procName) );
							}
						}
					}
				} catch ( Exception $e ) {
					$this->logger->error(
						sprintf( '%s %s - %s', $dataProvider->getHumanReadableName(),
							$operation->getHumanReadableName(), $e->getMessage()
						), $e
					);
					// debugging...
/*					$this->logger->error(
						sprintf( '%s %s - Type: %s, Message: %s, File: %s, Line: %s', $dataProvider->getHumanReadableName(),
							$operation->getHumanReadableName(), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()
						)
					);*/
				}
			}
		}
	}

	public function runAJAX() {
		$this->run();
		// todo: include run time and operation type
		$fHandleTxt = fopen( Church_Theme_Content_Integration::getLogFileName(), 'w' );
		if ( $fHandleTxt !== false ) {
			fwrite( $fHandleTxt, $this->logger->toString() );
			fclose( $fHandleTxt );
		}
		$fHandleHTML = fopen( Church_Theme_Content_Integration::getLogFileName('html'), 'w' );
		if ( $fHandleHTML !== false ) {
			fwrite( $fHandleHTML, $this->logger->toHTML() );
			fclose( $fHandleHTML );
		}
		// ajax output
		//echo $this->logger->toHTML();
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
			// test hack
			/*echo json_encode( array(
				'message' => 'A test message',
				'warnings' => 1,
				'warning_messages' => 'Something kind of bad has happened'
			));*/
		}
		die(); // needed to avoid wordpress returning a zero at the end of the response
	}
} 