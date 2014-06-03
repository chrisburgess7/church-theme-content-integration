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

	public function __construct( CTCI_LoggerInterface $logger ) {
		$this->logger = $logger;
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
		// ajax output
		echo $this->logger->toHTML();
		die(); // needed to avoid wordpress returning a zero at the end of the response
	}
} 