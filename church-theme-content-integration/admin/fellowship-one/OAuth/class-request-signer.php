<?php
require_once 'class-f1-api-util.php';

/**
 * Copyright 2009 Fellowship Technologies
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 *
 * Library to sign the requests
 *
 * @author Jaskaran Singh (Jas)
 */
class CTCI_RequestSigner {
	/*
	 * Creates the oauth_signature value to be included in the Authorization header
	 */
	public static function buildSignature( $consumerSecret, $tokenSecret, $httpMethod, $url, $oAuthOptions, &$debugInfo = array() ) {
		$requestSignerDebugInfo = array();

		$base_string = CTCI_RequestSigner::getSignatureBaseString( $httpMethod, $url, $oAuthOptions, $requestSignerDebugInfo );
		$requestSignerDebugInfo[ 'signature_base_string' ] = $base_string;
		$debugInfo[ 'request_signer' ] = $requestSignerDebugInfo;

		$key_parts = array(
			$consumerSecret,
			$tokenSecret
		);

		$key_parts = CTCI_F1APIUtil::urlencode_rfc3986( $key_parts );
		$key = implode( '&', $key_parts );

		return base64_encode( hash_hmac( 'sha1', $base_string, $key, true ) );
	}

	/**
	 * The Signature Base String is a consistent reproducible concatenation of the
	 * request elements into a single string. The string is used as an input in hashing or signing algorithms.
	 *
	 * The base string => $httpMethod&urlencode($url without query params)&urlencode(normalized_request_parameters)
	 * (Basically HTTP method(GET/POST etc), the URL(scheme://host/path, doesnt include the query string), and the norlalized request parameters
	 * each urlencoded and then concated with &.)
	 */
	private function getSignatureBaseString( $httpMethod, $url, $oAuthOptions, &$debugInfo ) {
		// Get the Query String parameters. Example if the request is http://photos.example.net/photos.aspx?file=vacation.jpg&size=original
		// then get the query string and create an array from it in form of key value pairs
		// $qsArray     Key     Value
		//              file    vacation.jpg
		//              size    original
		$parts = parse_url( $url );
		$qsArray = array();
		$qs = array();
		if ( isset( $parts[ 'query' ] ) ) {
			$qs = $parts[ 'query' ];
			parse_str( $qs, $qsArray );
		}
		$signable_options = array_merge( $oAuthOptions, $qsArray );
		$signable_parameters = CTCI_RequestSigner::getNormalizedRequestParameters( $signable_options );
		$normalized_url = CTCI_F1APIUtil::getNormalizedHttpUrl( $url );

		$debug = array();
		$debug[ 'HTTP Method' ] = $httpMethod;
		$debug[ 'Normalized Url' ] = $normalized_url;
		$debug[ 'Signable Parameters' ] = $signable_parameters;
		$debug[ 'Query parameters' ] = $qs;
		$debugInfo[ 'signature_base_string_parts' ] = $debug;

		$parts = array(
			$httpMethod, // GET or POST
			$normalized_url, // return "$scheme://$host$path";
			$signable_parameters
		);

		// Url encode each of http method, Url(without query string), and the normalized parameters (oauth parameters, along with query string parameters)
		$parts = CTCI_F1APIUtil::urlencode_rfc3986( $parts );
		// After url encoding, concatenate them with an &
		return implode( '&', $parts );
	}

	/*
	 * Returns Normalized Request Parameter string
	 * @params: Parameters that need to be included in the normalized string
	 *          params is an array. params contain all the Parameters that need to be used to generating the normalized parameter string
	 *          The Parameters that need to be passed in are:
	 *              -- Parameters in the OAUTH Authorization Header (eg: oauth_consumer_key,oauth_nonce, oauth_signature_method, oauth_timestamp etc.)
	 *                  NOTE: realm and oauth_signature are not used
	 *              -- Parameters included in HTTP POST request body ( WITH CONTENT-TYPE OF application/x-www-form-urlencoded )
	 *              -- HTTP GET parameters added to URLs in the query post (eg: for http://www.example.com/resource?id=123&name=jas
	 *                 the params array will include 2 entries with id=>123 and name=>jas)
	 * These are the following steps followed to generate the normalized request parameter string
	 * 1. Encode all the parameters in the $params array (Specifically encode all the keys and values in the $params array)
	 * 2. Sort all the entries in the parameters array
	 * 3. Concatenate the sorted entried into a single string. a) For each key in the array, key is seperated from the value by an '=' character (creating a name value pair of form name=value)
	 *    EVEN IF THE VALUE IS EMPTY. b) Each name value pair is seperated by an '&' character
	 *
	 * Example: User used HTTP GET to requrest an image
	 * http://photos.example.net/photos.aspx?file=vacation.jpg&size=original
	 * params will contain: Key                     value
	 *                      oauth_consumer_key      7
							oauth_nonce             d9678981968d24da32602222727a8c1f
							oauth_signature_method  HMAC-SHA1
							oauth_timestamp			1241025870
							oauth_version           1.0
	 *                      oauth_token             61344b1a-0e88-4ccc-8854-cc6219d83642
	 *                      file                    vacation.jpg
	 *                      size                    original
	 */
	private static function getNormalizedRequestParameters( $params ) {

		// Remove oauth_signature if present
		if ( isset( $params[ 'oauth_signature' ] ) ) {
			unset( $params[ 'oauth_signature' ] );
		}

		// STEP 1: Urlencode both keys and values
		$keys = CTCI_F1APIUtil::urlencode_rfc3986( array_keys( $params ) );
		$values = CTCI_F1APIUtil::urlencode_rfc3986( array_values( $params ) );
		$params = array_combine( $keys, $values );

		// STEP 2: Sort by keys (natsort)
		uksort( $params, 'strcmp' );

		// STEP 3. Concatenate the sorted entried into a single string
		// 3a) Generate key=value pairs
		$pairs = array();
		foreach ( $params as $key => $value ) {
			if ( is_array( $value ) ) {
				// If the value is an array, it's because there are multiple
				// with the same key, sort them, then add all the pairs
				natsort( $value );
				foreach ( $value as $v2 ) {
					$pairs[ ] = $key . '=' . $v2;
				}
			} else {
				$pairs[ ] = $key . '=' . $value;
			}
		}

		// 3b) Return the pairs, concated with &
		return implode( '&', $pairs );
	}
}

?>
