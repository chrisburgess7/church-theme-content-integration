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

	public function addDataProvider( CTCI_DataProviderInterface $dataProvider ) {
		$this->dataProviders[] = $dataProvider;
		return $this;
	}

	public function addOperation( CTCI_OperationInterface $operation ) {
		$this->operations[] = $operation;
		return $this;
	}

	public function run() {
		echo 'module run' . PHP_EOL;
		foreach ( $this->dataProviders as $dataProvider ) {
			try {
				$dataProvider->initDataProvider();
			} catch ( CTCI_AuthenticationException $e ) {
				// todo: handle authentication errors
			}
			echo 'init complete' . PHP_EOL;
			foreach ( $this->operations as $operation ) {
				$valid = $operation->setDataProvider( $dataProvider );
				if ( $valid ) {
					echo 'running...' . PHP_EOL;
					$operation->run();
				}
			}
		}
	}
} 