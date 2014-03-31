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
		//echo 'module run' . PHP_EOL;
		foreach ( $this->dataProviders as $dataProvider ) {
			try {
				$dataProvider->initDataProvider();
			} catch ( CTCI_AuthenticationException $e ) {
				$this->logger->error(
					'Authentication failed for provider ' . $dataProvider->getHumanReadableName() . '. ' .
					$e->getMessage()
				);
				continue;
			} catch ( Exception $e ) {
				$this->logger->error(
					'Init failed for provider ' . $dataProvider->getHumanReadableName() . '. ' .
					$e->getMessage()
				);
				continue;
			}
			//echo 'init complete' . PHP_EOL;
			foreach ( $this->operations as $operation ) {
				try {
					$valid = $operation->setDataProvider( $dataProvider );
					if ( $valid ) {
						//echo 'running...' . PHP_EOL;
						$operation->run();
					}
				} catch ( Exception $e ) {
					$this->logger->error( $e->getMessage() );
				}
			}
		}
		echo $this->logger->toHTML();
		die(); // needed to avoid wordpress returning a zero at the end of the response
	}
} 