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
		$this->logger->clear();

		// set 5 minute time limit, hopefully long enough...
		set_time_limit(300);

		foreach ( $this->dataProviders as $dataProvider ) {
			try {
				if ( ! $dataProvider->initDataProviderForProcess( $this->logger ) ) {
					$this->logger->error(
						'Init failed for provider ' . $dataProvider->getHumanReadableName() . '. '
					);
					continue;
				}
			} catch ( Exception $e ) {
				$this->logger->error(
					'Init failed for provider ' . $dataProvider->getHumanReadableName() . '. ' .
					$e->getMessage(), $e
				);
				continue;
			}
			try {
				if ( ! $dataProvider->authenticateForProcess() ) {
					$this->logger->error(
						'Authentication failed for provider ' . $dataProvider->getHumanReadableName() . '. '
					);
					continue;
				}
			} catch ( Exception $e ) {
				$this->logger->error(
					'Authentication failed for provider ' . $dataProvider->getHumanReadableName() . '. ' .
					$e->getMessage(), $e
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
								$this->logger->success( $procName . ' complete.' );
							} else {
								$this->logger->warning( $procName . ' has finished with warnings.' );
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
		echo $this->logger->toHTML();
		die(); // needed to avoid wordpress returning a zero at the end of the response
	}
} 