<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/02/14
 * Time: 4:33 PM
 */

require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/fellowship-one/OAuth/class-f1-oauth-client.php';
require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/fellowship-one/OAuth/class-f1-api-keys.php';
require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/fellowship-one/interface-f1-api-settings.php';
/*require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/class-settings-manager.php';
require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/class-wpal.php';*/

class F1Credentials implements CTCI_F1APISettingsInterface {

	public function getF1ConsumerKey() {
		return CTCI_F1APIKeys::$consumer_key;
	}

	public function getF1ConsumerSecret() {
		return CTCI_F1APIKeys::$consumer_secret;
	}

	public function getF1Username() {
		return CTCI_F1APIKeys::$username;
	}

	public function getF1Password() {
		return CTCI_F1APIKeys::$password;
	}

	public function getF1ServerBaseURL() {
		return CTCI_F1APIKeys::$base_url;
	}
}

$client = new CTCI_F1OAuthClient( new F1Credentials() );

$success = $client->authenticate();

var_dump( $success );

$lists = $client->json()->getPeopleLists();
$decodedLists = json_decode( $lists );

var_dump( $lists );

$list = $client->json()->getPeopleList( 1184627 );
$decodedList = json_decode( $list );

var_dump( $list );

$members = $client->json()->getPeopleListMembers( 1184627 );
$decodedMembers = json_decode( $members );

var_dump( $members );

$member = $client->json()->getPeopleListMember( 1184627, 504312835 );

var_dump( $member );

$person = $client->json()->getPerson( 52037997 );
$decodedPerson = json_decode( $person );

var_dump( $person );