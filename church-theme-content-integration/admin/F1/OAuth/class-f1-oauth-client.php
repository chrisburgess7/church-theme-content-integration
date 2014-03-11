<?php
/**
 * Copyright 2009 Fellowship Technologies
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 *
 * Core OAuth API Library
 *
 * This file has been modified by Chris Burgess.
 *
 * @author Jaskaran Singh (Jas)
 */

require_once 'interface-f1-oauth-client.php';
require_once 'class-request-signer.php';
require_once 'class-f1-app-config.php';
require_once 'class-f1-api-util.php';

class CTCI_F1OAuthClient implements CTCI_F1OAuthClientInterface {
	private $consumerKey = null;
	private $consumerSecret = null;

	private $username;
	private $password;

	// This variable is used to store Request Token or the Access token
	private $accessToken; // oauth_token
	private $tokenSecret = ""; // oauth_token_secret

	// The Base URL for the service provider
	private $baseUrl = null;
	private $requesttoken_path = null;
	private $accesstoken_path = null;

	// Connection to the Host
	private $connection;
	// Array. The response Headers. This will be used ONLY when the consumer requests an access token.
	// Along with the access token, the response header includes Content-Location header.
	// This Header contains the link to the person associated with the access token
	private $responseHeaders;

	// An array to log the request and the response. Alos logs other things like
	// the HTTP Code returned
	private $logInfo;
	// var $lineBreak = "\r\n";
	var $lineBreak = "<br/>";

	private $format = 'json';
	private $formatHeader;
	private $jsonFormatHeader = array( "Accept: application/json", "Content-type: application/json" );
	private $xmlFormatHeader = array( "Accept: application/xml", "Content-type: application/xml" );

	public function __construct( CTCI_F1APISettingsInterface $settings ) {
		$this->consumerKey = $settings->getF1ConsumerKey();
		$this->consumerSecret = $settings->getF1ConsumerSecret();
		$this->baseUrl = $settings->getF1ServerBaseURL();

		$this->init_curl();
		$this->setPathsFromConfig();

		$this->username = $settings->getF1Username();
		$this->password = $settings->getF1Password();

		$this->format = 'json';
		$this->formatHeader = $this->jsonFormatHeader;
	}

	/*
	 * Initialize the libCurl library functions
	 */
	private function init_curl() {
		// Create a connection
		$this->connection = curl_init();

		// Initialize the CURL Connection

		// Important. if the CURLOPT_RETURNTRANSFER  option is set, curl_exec it will return the result on success, FALSE on failure.
		curl_setopt( $this->connection, CURLOPT_RETURNTRANSFER, true );
		// The CURLOPT_HEADER option sets whether or not the server response header should be returned
		curl_setopt( $this->connection, CURLOPT_HEADER, false );
		// track request information. it allows the user to retrieve the request sent
		// by cURL to the server. This is very handy and necessary when trying to analyze the full content
		// of the client to server communication. You use
		// curl_getinfo($ch, CURLINFO_HEADER_OUT) to retrieve the request as a string
		curl_setopt( $this->connection, CURLINFO_HEADER_OUT, true );
		// Verifies if the remote server has a valid certificate. Set this to false in case the remote server
		// has invalid certificate
		curl_setopt( $this->connection, CURLOPT_SSL_VERIFYPEER, false );
	}

	/*
	 * Reads Paths from the CTCI_F1AppConfig file
	 */
	private function setPathsFromConfig() {
		$this->requesttoken_path = CTCI_F1AppConfig::$requesttoken_path;
		$this->accesstoken_path = CTCI_F1AppConfig::$accesstoken_path;
	}

	public function setUsername( $username ) {
		$this->username = $username;
	}

	public function setPassword( $password ) {
		$this->password = $password;
	}

	public function authenticate() {
		// To authenticate the user and get the access token, the consumer posts the credentials to the service provider
		$requestURL = sprintf( "%s%s", $this->baseUrl, CTCI_F1AppConfig::$accesstoken_path );
		// SET the username and password
		$requestBody = CTCI_F1APIUtil::urlencode_rfc3986(
			base64_encode( sprintf( "%s %s", $this->username, $this->password ) )
		);

		// This is important. If we don't set this, the post will be sent using Content-Type: application/x-www-form-urlencoded (curl will do this automatically)
		// Per OAuth specification, if the Content-Type is application/x-www-form-urlencoded, then all the post parameters also need to be part of the base signature string
		// To override this, we need to set Content-type to something other than application/x-www-form-urlencoded
		$getContentType = array( "Accept: application/json", "Content-type: application/json" );
		$requestBody = $this->postRequest( $requestURL, $requestBody, $getContentType, 200 );
		preg_match( "~oauth_token\=([^\&]+)\&oauth_token_secret\=([^\&]+)~i", $requestBody, $tokens );
		if ( !isset( $tokens[ 1 ] ) || !isset( $tokens[ 2 ] ) ) {
			return false;
		}
		$this->accessToken = $tokens[ 1 ];
		$this->tokenSecret = $tokens[ 2 ];

		return true;
	}

	public function json() {
		$this->format = 'json';
		$this->formatHeader = $this->jsonFormatHeader;
		return $this;
	}

	public function xml() {
		$this->format = 'xml';
		$this->formatHeader = $this->xmlFormatHeader;
		return $this;
	}

	public function getPeopleLists() {
		return $this->doRequest(
			sprintf( "%s%s", $this->baseUrl, CTCI_F1AppConfig::$f1_peoplelist_list ),
			$this->formatHeader
		);
	}

	public function getPeopleList( $peopleListId ) {
		return $this->doRequest(
			sprintf( "%s%s", $this->baseUrl, str_replace( '{id}', $peopleListId, CTCI_F1AppConfig::$f1_peoplelist_show ) ),
			$this->formatHeader
		);
	}

	public function getPeopleListMembers( $peopleListId ) {
		return $this->doRequest(
			sprintf( "%s%s", $this->baseUrl, str_replace( '{id}', $peopleListId, CTCI_F1AppConfig::$f1_peoplelistmembers_list ) ),
			$this->formatHeader
		);
	}

	public function getPeopleListMember( $peopleListId, $memberId ) {
		$urlPath = str_replace( '{peopleListID}', $peopleListId, CTCI_F1AppConfig::$f1_peoplelistmembers_show );
		$urlPath = str_replace( '{id}', $memberId, $urlPath );
		return $this->doRequest(
			sprintf( "%s%s", $this->baseUrl, $urlPath ),
			$this->formatHeader
		);
	}

	/*
	 * Make a request using HTTP GET
	 */
	protected function doRequest( $requestURL, $nonOAuthHeader = array(), $successHttpCode = 200 ) {
		return $this->sendRequest( "GET", trim( $requestURL ), $nonOAuthHeader, "", $successHttpCode );
	}

	/*
	 * Make a request using HTTP Post
	 */
	protected function postRequest( $requestURL, $requestBody = "", $nonOAuthHeader = array(), $successHttpCode = 201 ) {
		return $this->sendRequest( "POST", trim( $requestURL ), $nonOAuthHeader, $requestBody, $successHttpCode );
	}

	/*
	* Make a request using HTTP PUT
	*/
	protected function putRequest( $requestURL, $requestBody = "", $nonOAuthHeader = array(), $successHttpCode = 200 ) {
		return $this->sendRequest( "PUT", trim( $requestURL ), $nonOAuthHeader, $requestBody, $successHttpCode );
	}

	private function sendRequest( $httpMethod, $requestURL, $nonOAuthHeader = array(), $requestBody = "", $successHttpCode = 201 ) {
		// 0 = call is being made to request a requestToken
		// 1 = call is being made to request an accessToken
		// 2 = call is being made to request a protected resources

		$tokenType = 2;
		$relativePath = str_ireplace( $this->baseUrl, "", $requestURL );
		if ( strcasecmp( $relativePath, $this->requesttoken_path ) == 0 ) {
			$tokenType = 0;
		} else {
			if ( strcasecmp( $relativePath, $this->accesstoken_path ) == 0 ) {
				$tokenType = 1;
			}
		}

		$oAuthHeader = array();
		$this->logInfo = array();
		$oAuthHeader[ ] = $this->getOAuthHeader( $httpMethod, $requestURL, $tokenType );

		//register a callback function which will process the response headers
		$this->responseHeaders = array();
		curl_setopt( $this->connection, CURLOPT_HEADERFUNCTION, array( &$this, 'readHeader' ) );

		if ( $httpMethod == "POST" || $httpMethod == "PUT" ) {
			curl_setopt( $this->connection, CURLOPT_POST, true );
			if ( strlen( $requestBody ) > 0 ) {
				curl_setopt( $this->connection, CURLOPT_POSTFIELDS, $requestBody );
			}
		} else {
			curl_setopt( $this->connection, CURLOPT_POST, false );
		}

		$httpHeaders = array_merge( $oAuthHeader, $nonOAuthHeader );

		if ( CTCI_F1AppConfig::$simulateRequest ) {
			print $this->lineBreak . "[--------------------BEGIN Simulate Request for $requestURL----------------------------]" . $this->lineBreak;
			$requestSimulator = sprintf( "%s %s HTTP/1.1" . $this->lineBreak, $httpMethod, $relativePath );
			foreach ( $httpHeaders as $header ) {
				$requestSimulator .= $header . $this->lineBreak;
			}

			$requestSimulator .= $requestBody;
			print $requestSimulator;
			print $this->lineBreak . "[--------------------END Simulate Request----------------------------]" . $this->lineBreak;
			print $this->lineBreak . "[--------------------BEGIN DEBUG----------------------------]" . $this->lineBreak;
			print "<pre>" . print_r( $this->logInfo, true ) . "</pre>";
			print $this->lineBreak . "[---------------------END DEBUG-----------------------------]" . $this->lineBreak;

			return null;
		}

		curl_setopt( $this->connection, CURLOPT_URL, $requestURL );
		curl_setopt( $this->connection, CURLOPT_HTTPHEADER, $httpHeaders );

		$responseBody = curl_exec( $this->connection );
		$info = curl_getinfo( $this->connection );
		$this->logRequest( $responseBody, $requestBody, $info );
		if ( !curl_errno( $this->connection ) ) // If there is no error
		{
			if ( $info[ 'http_code' ] === $successHttpCode ) {
				return $responseBody;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 *    Create a random "nonce" for every oAuth Request.
	 */
	private function getOAuthNonce() {
		return md5( microtime() . rand( 500, 1000 ) );
	}

	/*
	 * Builds OAuthHeader to be sent in a Request Token request. This method is used
	 * to created the "Authorization" Header
	 */
	private function buildOAuthHeader( $oAuthOptions ) {
		$requestValues = array();

		foreach ( $oAuthOptions as $oAuthKey => $oAuthValue ) {
			if ( substr( $oAuthKey, 0, 6 ) != "oauth_" ) {
				continue;
			}

			if ( is_array( $oAuthValue ) ) {
				foreach ( $oAuthValue as $valueKey => $value ) {
					$requestValues[ ] = sprintf( "%s=%s", $valueKey, rawurlencode( utf8_encode( $value ) ) );
				}
			} else {
				$requestValues[ ] = sprintf( "%s=%s", $oAuthKey, rawurlencode( utf8_encode( $oAuthValue ) ) );
			}
		}

		$requestValues = implode( ",", $requestValues );

		return $requestValues;
	}

	/*
	 * Returns a string of the format Authorization: <auth_string>
	 * @param tokenType: Type of token 0==request token. > 0 Access token and other requests
	 */
	private function getOAuthHeader( $httpMethod, $requestURL ) {
		$oAuthHeaderValues = array(
			"oauth_consumer_key" => $this->consumerKey,
			"oauth_nonce" => $this->getOAuthNonce(),
			"oauth_signature_method" => "HMAC-SHA1",
			"oauth_timestamp" => mktime(),
			"oauth_version" => "1.0"
		);

		if ( strlen( $this->accessToken ) > 0 ) {
			$oAuthHeaderValues[ "oauth_token" ] = $this->accessToken;
		}

		$oAuthHeaderValues[ "oauth_signature" ] = CTCI_RequestSigner::buildSignature(
			$this->consumerSecret, $this->tokenSecret, $httpMethod, $requestURL, $oAuthHeaderValues, $this->logInfo
		);

		$oauthHeader = $this->buildOAuthHeader( $oAuthHeaderValues );

		return sprintf( "Authorization: %s", $oauthHeader );
	}

	private function logRequest( $responseBody, $requestBody = "", $transferInfo = array() ) {
		// The request string sent
		$requestString = curl_getinfo( $this->connection, CURLINFO_HEADER_OUT );

		$debugArray = array();
		$requestArray = array();
		$responseArray = array();

		$requestArray[ 'request_body' ] = $requestBody;
		$requestArray[ 'CURLINFO_HEADER_OUT' ] = $requestString;

		$responseArray[ 'RESPONSE_HEADERS' ] = $this->responseHeaders;
		$responseArray[ 'response_body' ] = $responseBody;

		$debugArray[ 'GET_INFO' ] = $transferInfo;
		$debugArray[ 'request' ] = $requestArray;
		$debugArray[ 'response' ] = $responseArray;

		// $this->logInfo = $debugArray;
		$this->logInfo = array_merge( $this->logInfo, $debugArray );

		if ( CTCI_F1AppConfig::$debug ) {
			print $this->lineBreak . "[--------------------BEGIN DEBUG----------------------------]" . $this->lineBreak;
			print "<pre>" . print_r( $this->logInfo, true ) . "</pre>";
			print $this->lineBreak . "[---------------------END DEBUG-----------------------------]" . $this->lineBreak;
		}
	}

	/*
	 *
	 * Callback function to parse Response Headers
	 * This function will be called for each response header parsed
	 * @ch cURL connection object
	 * @header The header value being parsed
	 */
	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function readHeader( /** @noinspection PhpUnusedParameterInspection */
		$ch, $header ) {
		$length = strlen( $header );
		$this->responseHeaders[ ] = $header;
		return $length;
	}
} 