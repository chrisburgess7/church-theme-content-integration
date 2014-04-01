<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/02/14
 * Time: 4:33 PM
 */

require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/fellowship-one/OAuth/class-f1-oauth-client.php';
require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/class-settings-manager.php';
require_once dirname( __FILE__ ) . '/../../../../../church-theme-content-integration/admin/class-wpal.php';

$settingsManager = new CTCI_SettingsManager(new CTCI_WPAL());

$client = new CTCI_F1OAuthClient($settingsManager);

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
