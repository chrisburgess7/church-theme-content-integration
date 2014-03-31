<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/03/14
 * Time: 1:49 PM
 */

interface CTCI_FunctionInterface {
	/**
	 * Must return a string uniquely identifying this function.
	 * Lowercase, words separated by underscore
	 * @return string
	 */
	public static function getTag();
	public static function getHumanReadableName();
	public function setDataProvider( CTCI_DataProviderInterface $dataProvider );
	public function run();
} 