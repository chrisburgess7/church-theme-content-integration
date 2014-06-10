<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 25/04/14
 * Time: 10:24 AM
 */

require_once dirname( __FILE__ ) . '/interface-data-provider.php';
require_once dirname( __FILE__ ) . '/interface-operation.php';

interface CTCI_HtmlHelperInterface {
	public function showAJAXRunButton( $label, $key, $enabled = true  );
	public function showAJAXRunButtonFor( CTCI_DataProviderInterface $provider, CTCI_OperationInterface $operation, $enabled = true  );
	public function showActionButton( $actionValue, $inputName, $inputId, $buttonTitle, $enabled = true  );
	public function showAJAXButton( $label, $key, $enabled = true );
} 