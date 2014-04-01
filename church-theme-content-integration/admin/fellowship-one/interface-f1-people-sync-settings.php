<?php

/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 12:28 PM
 */
interface CTCI_F1PeopleSyncSettingsInterface {
	public function getF1PeopleLists();

	public function f1SyncGroups();

	public function f1NameFormat();
	/*
	 * leave this our for now, put back in once clear that current approach is correct
		public function f1SyncPersonPhone();

		public function f1SyncPersonEmail();

		public function f1SyncPersonFacebookURL();

		public function f1SyncPersonTwitterURL();

		public function f1SyncPersonLinkedInURL();*/
} 