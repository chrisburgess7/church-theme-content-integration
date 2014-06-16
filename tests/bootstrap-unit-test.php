<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 16/06/14
 * Time: 11:14 AM
 */

// define some global things that our classes may need access to

class Church_Theme_Content_Integration {
	public static $TEXT_DOMAIN = 'church-theme-content-integration';
}

function __( $msg, /** @noinspection PhpUnusedParameterInspection */
             $textDomain ) {
	return $msg;
}

function _e( $msg, /** @noinspection PhpUnusedParameterInspection */
             $textDomain ) {
	echo $msg;
}

function _n( $msg, $pluralMsg, $count, /** @noinspection PhpUnusedParameterInspection */
             $textDomain ) {
	if ( $count == 1 ) {
		return $msg;
	} else {
		return $pluralMsg;
	}
}
