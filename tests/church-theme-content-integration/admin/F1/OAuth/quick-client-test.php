<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 28/02/14
 * Time: 4:33 PM
 */

require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/F1/OAuth/CTCIF1OAuthClient.php';

$client = new CTCI_F1OAuthClient();

$success = $client->authenticate();

var_dump( $success );

$lists = $client->json()->getPeopleLists();

var_dump( $lists );

$list = $client->xml()->getPeopleList( 1184627 );

var_dump( $list );

$members = $client->xml()->getPeopleListMembers( 1184627 );

var_dump( $members );

$member = $client->json()->getPeopleListMember( 1184627, 504312835 );

var_dump( $member );
