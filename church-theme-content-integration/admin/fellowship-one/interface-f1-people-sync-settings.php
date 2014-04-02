<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 12:28 PM
 */
interface CTCI_F1PeopleSyncSettingsInterface {
	public function getF1PeopleLists();

	public function f1SyncPeopleGroups();

	public function f1NameFormat();

	public function f1SyncPersonPosition();
	public function f1PersonPositionAttribute();

    public function f1SyncPersonPhone();

	public function f1SyncPersonEmail();

	public function f1SyncPersonFacebookURL();

	public function f1SyncPersonTwitterURL();

	public function f1SyncPersonLinkedInURL();
} 