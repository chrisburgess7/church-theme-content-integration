<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 11:59 AM
 */
interface CTCI_F1APISettingsInterface {
	public function getAuthenticationMode();

	public function getF1ConsumerKey();

	public function getF1ConsumerSecret();

	public function getF1Username();

	public function getF1Password();

	//public function getF1ChurchCode();

	public function getF1ServerBaseURL();
} 