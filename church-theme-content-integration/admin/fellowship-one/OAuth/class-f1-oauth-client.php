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

	const OAUTH = 3;
	const CREDENTIALS = 2;

	const REQUEST_REQUEST_TOKEN = 0;
	const REQUEST_ACCESS_TOKEN = 1;
	const REQUEST_RESOURCE = 2;

	protected $authMode = self::OAUTH;

	private $consumerKey = null;
	private $consumerSecret = null;

	private $username;
	private $password;

	// This variable is used to the Access token
	private $requestToken; // oauth_token
	private $requestTokenSecret = ""; // oauth_token_secret

	// This variable is used to the Access token
	private $accessToken; // oauth_token
	private $accessTokenSecret = ""; // oauth_token_secret

	// The Base URL for the service provider
	private $baseUrl = null;
	private $requesttoken_path = null;
	private $accesstoken_path = null;
	private $auth_path = null;

	// The URL to redirect to after successful authentication by the Service Provider
	private $callbackUrl = null;

	// Connection to the Host
	private $connection;
	// Array. The response Headers. This will be used ONLY when the consumer requests an access token.
	// Along with the access token, the response header includes Content-Location header.
	// This Header contains the link to the person associated with the access token
	private $responseHeaders;

	private $format = 'json';
	private $formatHeader;
	private $jsonFormatHeader = array( "Accept: application/json", "Content-type: application/json" );
	private $xmlFormatHeader = array( "Accept: application/xml", "Content-type: application/xml" );

	public function __construct( CTCI_F1APISettingsInterface $settings ) {
		$this->authMode = $settings->getAuthenticationMode();

		$this->consumerKey = $settings->getF1ConsumerKey();
		$this->consumerSecret = $settings->getF1ConsumerSecret();
		$this->baseUrl = $settings->getF1ServerBaseURL();

		$this->init_curl();
		$this->setPathsFromConfig();

		if ( $this->authMode === self::CREDENTIALS ) {
			$this->username = $settings->getF1Username();
			$this->password = $settings->getF1Password();
		}

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
		if ( $this->authMode === self::CREDENTIALS ) {
			$this->accesstoken_path = CTCI_F1AppConfig::$accesstoken_path_2ndparty;
		} else {
			$this->accesstoken_path = CTCI_F1AppConfig::$accesstoken_path_3rdparty;
		}
		$this->auth_path = CTCI_F1AppConfig::$auth_path;
	}

	public function setCallbackURL( $url ) {
		$this->callbackUrl = $url;
		return $this;
	}

	public function setUsername( $username ) {
		$this->username = $username;
		return $this;
	}

	public function setPassword( $password ) {
		$this->password = $password;
		return $this;
	}

	public function authenticate() {
		if ( $this->authMode === self::CREDENTIALS ) {
			// To authenticate the user and get the access token, the consumer posts the credentials to the service provider
			$requestURL = sprintf( "%s%s", $this->baseUrl, $this->accesstoken_path );
			// SET the username and password
			$requestBody = CTCI_F1APIUtil::urlencode_rfc3986(
				base64_encode( sprintf( "%s %s", $this->username, $this->password ) )
			);
			// This is important. If we don't set this, the post will be sent using Content-Type: application/x-www-form-urlencoded (curl will do this automatically)
			// Per OAuth specification, if the Content-Type is application/x-www-form-urlencoded, then all the post parameters also need to be part of the base signature string
			// To override this, we need to set Content-type to something other than application/x-www-form-urlencoded
			$getContentType = array( "Accept: application/json", "Content-type: application/json" );
			$requestBody = $this->sendRequest( "POST", $requestURL, $getContentType, $requestBody, 200, self::REQUEST_ACCESS_TOKEN );
			preg_match( "~oauth_token\=([^\&]+)\&oauth_token_secret\=([^\&]+)~i", $requestBody, $tokens );
			if ( !isset( $tokens[ 1 ] ) || !isset( $tokens[ 2 ] ) ) {
				return false;
			}
			$this->accessToken = $tokens[ 1 ];
			$this->accessTokenSecret = $tokens[ 2 ];

			return true;
		} elseif ( $this->authMode === self::OAUTH ) {
			// First step is to get the Request Token (oauth_token)
			if ( ! $this->getRequestToken() ) {
				return false;
			}
			// Using the oauth_token take the user to Service Provider’s login screen.
			// Also provide a “callback” which the url to which the service provider redirects after the credentials are authenticated at the service provider side.
			if(CTCI_F1AppConfig::$includeRequestSecretInUrl) {
				$parts = parse_url($this->callbackUrl);
				$query = $parts['query'];
				if(strlen($query)>0) {
					$this->callbackUrl = $this->callbackUrl.'&oauth_token_secret='.$this->requestTokenSecret;
				} else {
					$this->callbackUrl = $this->callbackUrl.'?oauth_token_secret='.$this->requestTokenSecret;
				}
			}

			$callbackURI = rawurlencode( $this->callbackUrl );

			$authenticateURL = sprintf( "%s%s?oauth_token=%s",
				$this->baseUrl, $this->auth_path, $this->requestToken );

			if( !empty( $callbackURI ) ) {
				$authenticateURL	.= sprintf( "&oauth_callback=%s", $callbackURI );
			}

			header( "Location: " . $authenticateURL );
			return true;
		}

		return false;
	}

	/**
	 *	Get a Request Token from the Service Provider for 3rd party auth
	 */
	protected function getRequestToken() {
		$requestURL	= sprintf( "%s%s", $this->baseUrl, $this->requesttoken_path );

		$requestBody	= $this->sendRequest( "POST", $requestURL,  array( 'Content-Length: 0'), "", 200, self::REQUEST_REQUEST_TOKEN );

		preg_match( "~oauth_token\=([^\&]+)\&oauth_token_secret\=([^\&]+)~i", $requestBody, $tokens );
		if( !isset( $tokens[1] ) || !isset( $tokens[2] ) ) {
			return false;
		}

		$this->requestToken = $tokens[1] ;
		$this->requestTokenSecret = $tokens[2] ;
		if( strlen( $this->requestToken ) > 0 && strlen( $this->requestTokenSecret ) > 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Get an Access Token from the Service Provider for 3rd party authentication.
	 * @param string $oauthToken The $oauthToken authorized request token. This token
	 *                              is returned by the service provider when the user authenticates
	 *                              on the service provider side. Use this request token to request a Access token
	 * @param string $tokenSecret
	 * @return bool
	 */
	public function retrieveAccessToken($oauthToken, $tokenSecret) {

		$this->requestToken = $oauthToken;
		$this->requestTokenSecret = $tokenSecret;

		$requestURL	= sprintf( "%s%s", $this->baseUrl, $this->accesstoken_path );

		curl_setopt( $this->connection, CURLOPT_NOBODY, true );

		$requestBody	= $this->sendRequest( "POST", $requestURL,  array( 'Content-Length: 0' ), "", 200, self::REQUEST_ACCESS_TOKEN );

		preg_match( "~oauth_token\=([^\&]+)\&oauth_token_secret\=([^\&]+)~i", $requestBody, $tokens );
		if( !isset( $tokens[1] ) || !isset( $tokens[2] ) ) {
			return false;
		}

		$this->accessToken = $tokens[1];
		$this->accessTokenSecret = $tokens[2];

		return true;
	}

	public function setAccessToken( $token ) {
		$this->accessToken = $token;
		return $this;
	}

	public function setAccessTokenSecret( $tokenSecret ) {
		$this->accessTokenSecret = $tokenSecret;
		return $this;
	}

	public function getAccessToken() {
		return $this->accessToken;
	}

	public function getAccessTokenSecret() {
		return $this->accessTokenSecret;
	}

	/**
	 * @return $this
	 */
	public function json() {
		$this->format = 'json';
		$this->formatHeader = $this->jsonFormatHeader;
		return $this;
	}

	/**
	 * @return $this
	 */
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

	public function getPerson( $personId ) {
		$urlPath = str_replace( '{id}', $personId, CTCI_F1AppConfig::$f1_people_show );
		return $this->doRequest(
			sprintf( "%s%s", $this->baseUrl, $urlPath ),
			$this->formatHeader
		);
	}

	public function getPersonCommunications( $personId ) {
		$urlPath = str_replace( '{personID}', $personId, CTCI_F1AppConfig::$f1_people_communications );
		return $this->doRequest(
			sprintf( "%s%s", $this->baseUrl, $urlPath ),
			$this->formatHeader
		);
	}

	public function getPersonAttributes( $personId ) {
		$urlPath = str_replace( '{peopleID}', $personId, CTCI_F1AppConfig::$f1_peopleAttributes_list );
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

	private function sendRequest( $httpMethod, $requestURL, $nonOAuthHeader = array(), $requestBody = "", $successHttpCode = 200, $requestType = self::REQUEST_RESOURCE ) {
		// 0 = call is being made to request a requestToken
		// 1 = call is being made to request an accessToken
		// 2 = call is being made to request a protected resources

		/*$tokenType = 2;
		$relativePath = str_ireplace( $this->baseUrl, "", $requestURL );
		if ( strcasecmp( $relativePath, $this->requesttoken_path ) == 0 ) {
			$tokenType = 0;
		} else {
			if ( strcasecmp( $relativePath, $this->accesstoken_path ) == 0 ) {
				$tokenType = 1;
			}
		}*/

		$oAuthHeader = array();
		$oAuthHeader[ ] = $this->getOAuthHeader( $httpMethod, $requestURL, $requestType );

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

		curl_setopt( $this->connection, CURLOPT_URL, $requestURL );
		curl_setopt( $this->connection, CURLOPT_HTTPHEADER, $httpHeaders );

		$responseBody = curl_exec( $this->connection );
		$info = curl_getinfo( $this->connection );

		if ( !curl_errno( $this->connection ) ) // If there is no error
		{
			if ( $info[ 'http_code' ] === $successHttpCode ) {
				return $responseBody;
			} else {
				throw new CTCI_F1APIRequestException(
					$requestURL, $httpHeaders, $info['http_code'], $responseBody
				);
			}
		} else {
			throw new CTCI_CURLException( curl_errno( $this->connection ) );
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
	private function getOAuthHeader( $httpMethod, $requestURL, $requestType ) {
		$oAuthHeaderValues = array(
			"oauth_consumer_key" => $this->consumerKey,
			"oauth_nonce" => $this->getOAuthNonce(),
			"oauth_signature_method" => "HMAC-SHA1",
			"oauth_timestamp" => mktime(),
			"oauth_version" => "1.0"
		);

		switch ( $requestType ) {
			case self::REQUEST_ACCESS_TOKEN:
				if ( strlen( $this->requestToken ) > 0 ) {
					$oAuthHeaderValues[ "oauth_token" ] = $this->requestToken;
				}
				$oAuthHeaderValues[ "oauth_signature" ] = CTCI_RequestSigner::buildSignature(
					$this->consumerSecret, $this->requestTokenSecret, $httpMethod, $requestURL, $oAuthHeaderValues
				);
				break;
			case self::REQUEST_RESOURCE:
			default:
				if ( strlen( $this->accessToken ) > 0 ) {
					$oAuthHeaderValues[ "oauth_token" ] = $this->accessToken;
				}
				$oAuthHeaderValues[ "oauth_signature" ] = CTCI_RequestSigner::buildSignature(
					$this->consumerSecret, $this->accessTokenSecret, $httpMethod, $requestURL, $oAuthHeaderValues
				);
				break;
		}

		$oauthHeader = $this->buildOAuthHeader( $oAuthHeaderValues );

		return sprintf( "Authorization: %s", $oauthHeader );
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

class CTCI_F1APIRequestException extends Exception {
	protected $requestURL;
	protected $requestHeaders;
	protected $http_code;
	protected $responseBody;

	/**
	 * @param string $requestURL
	 * @param mixed $requestHeaders
	 * @param int $http_code
	 * @param string $responseBody
	 * @param string $message
	 * @param int $code
	 * @param null $innerException
	 */
	public function __construct( $requestURL, $requestHeaders, $http_code, $responseBody, $message = '', $code = 0, $innerException = null ) {
		parent::__construct(
			sprintf(
				'An error occurred attempting to send a request to the service provider. Request URL: %s, HTTP Code: %s, Response: %s',
				$requestURL, $http_code, $responseBody
			), $code, $innerException
		);
		$this->requestURL = $requestURL;
		$this->requestHeaders = $requestHeaders;
		$this->http_code = $http_code;
		$this->responseBody = $responseBody;
	}

	public function getRequestURL() {
		return $this->requestURL;
	}

	public function getRequestHeaders() {
		return $this->requestHeaders;
	}

	public function getHttpCode() {
		return $this->http_code;
	}

	public function getResponseBody() {
		return $this->responseBody;
	}

	public function __toString() {
		return sprintf( "exception '%s' with message '%s' HTTP Headers: '%s' Stack trace: %s",
			get_class( $this ), $this->message, print_r( $this->requestHeaders, true ), $this->getTraceAsString()
		);
	}
}

class CTCI_CURLException extends Exception {
	public function __construct( $errno, $message = '', $innerException = null ) {
		parent::__construct( $message, $errno, $innerException );
	}
}