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
 * @author Jaskaran Singh (Jas)
 */

require_once 'RequestSigner.php';
require_once 'AppConfig.php';
require_once 'Util.php';


/**
 * Client library for OAuth
 *
 * @author Jaskaran Singh
 */
class OAuthClient {

    private $consumerKey = null;
    private $consumerSecret = null;

    // This variable is used to store Request Token or the Access token
    private $requestToken; // oauth_token
    private $tokenSecret = "";  // oauth_token_secret

    // The Base URL for the service provider
    private $baseUrl = null;
    private $requesttoken_path = null;
    private $accesstoken_path = null;
    private $auth_path = null;
    // The URL to redirect to after succefull authentication by the Service Provider
    private $callbackUrl = null;
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

    public function __construct($baseUrl, $consumerKey, $consumerSecret) {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->baseUrl = $baseUrl;

        $this->init_curl();
        $this->setPathsFromConfig();
    }

    /*
     * Initialize the libCurl library functions
     */
    private function init_curl() {
        // Create a connection
        $this->connection	= curl_init();

        // Initialize the CURL Connection

        // Important. if the CURLOPT_RETURNTRANSFER  option is set, curl_exec it will return the result on success, FALSE on failure.
        curl_setopt( $this->connection, CURLOPT_RETURNTRANSFER, true );
        // The CURLOPT_HEADER option sets whether or not the server response header should be returned
        curl_setopt( $this->connection, CURLINFO_HEADER, false );
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
     * Initialize the Access token and token secret. Make this call to set the
     * Access token and token secret before accessing any protected resources
     */
    public function initAccessToken($access_token, $token_secret) {
        $this->requestToken = $access_token;
        $this->tokenSecret = $token_secret;
    }

    /************* START- Path Setters ********************/
    public function setRequestTokenPath( $requestPath ) {
       $this->requesttoken_path	= $requestPath;
    }

    public function setAccessTokenPath( $accessPath ) {
        $this->accesstoken_path	= $accessPath;
    }

    public function setAuthPath( $authPath ) {
        $this->auth_path = $authPath;
    }

    public function setCallback( $callbackURI ) {
        $this->callbackUrl = $callbackURI;
    }
    /*
     * Reads Paths from the CTCI_F1AppConfig file
     */
    public function setPathsFromConfig(){
        $this->requesttoken_path	= CTCI_F1AppConfig::$requesttoken_path;
        $this->accesstoken_path	= CTCI_F1AppConfig::$accesstoken_path;
        $this->auth_path = CTCI_F1AppConfig::$auth_path;
        $this->callbackUrl = CTCI_F1AppConfig::$callbackURI;
    }
    /************* END- Path Setter **********************/
    
    /**************START- Property Setters****************/
    public function setConsumerKey($consumerKey) {
        $this->consumerKey = $consumerKey;
    }

    public function setConsumerSecret($consumerSecret) {
        $this->consumerSecret = $consumerSecret;
    }
    /**************END- Property Setters******************/

    /************* START- TOKEN GETTERS ******************/
    /*
     * Gets the token. The token may be a request token or an access token depending
     * upon the context
     */
    public function getToken() {
        return $this->requestToken;
    }

    public function getTokenSecret() {
        return $this->tokenSecret;
    }
    /************* END- TOKEN GETTERS ********************/
    /*
     * Gets the response headers. An Array is returned, each entry of which is a response header
     */
    public function getResponseHeader(){
        return $this->responseHeaders;
    }

    public function getBaseUrl() {
        return  $this->baseUrl;
    }

    public function setBaseUrl($baseUrl){
        $this->baseUrl = $baseUrl;
    }
    /*
     * Used for debugging purposes. This returns an array containing the Request and the Response
     */
    public function getLoggingInfo() {
        return $this->logInfo;
    }

    private function sendRequest( $httpMethod, $requestURL, $nonOAuthHeader = array(), $requestBody = "", $successHttpCode = 201 ) {
        // 0 = call is being made to request a requestToken
        // 1 = call is being made to request an accessToken
        // 2 = call is being made to request a protected resources

        $tokenType = 2;
        $relativePath = str_ireplace($this->baseUrl, "", $requestURL);
        if (strcasecmp($relativePath, $this->requesttoken_path) == 0)
        $tokenType = 0;
        else if(strcasecmp($relativePath, $this->accesstoken_path) == 0)
        $tokenType = 1;

        $oAuthHeader = array();
        $this->logInfo = array();
        $oAuthHeader[] = $this->getOAuthHeader($httpMethod, $requestURL, $tokenType);

        //register a callback function which will process the response headers
        $this->responseHeaders = array();
        curl_setopt($this->connection, CURLOPT_HEADERFUNCTION, array(&$this,'readHeader'));

        if( $httpMethod == "POST" || $httpMethod == "PUT") {
            curl_setopt( $this->connection, CURLOPT_POST, true );
            if(strlen($requestBody) > 0)
            curl_setopt( $this->connection, CURLOPT_POSTFIELDS, $requestBody );
        } else {
            curl_setopt( $this->connection, CURLOPT_POST, false );
        }

        $httpHeaders = array_merge($oAuthHeader, $nonOAuthHeader);

        if(CTCI_F1AppConfig::$simulateRequest) {
            print $this->lineBreak."[--------------------BEGIN Simulate Request for $requestURL----------------------------]".$this->lineBreak;
            $requestSimulator = sprintf( "%s %s HTTP/1.1".$this->lineBreak, $httpMethod, $relativePath );
            foreach ($httpHeaders as $header)
            $requestSimulator .=  $header.$this->lineBreak;

            $requestSimulator .= $requestBody;
            print $requestSimulator;
            print $this->lineBreak."[--------------------END Simulate Request----------------------------]".$this->lineBreak;
            print $this->lineBreak."[--------------------BEGIN DEBUG----------------------------]".$this->lineBreak;
            print "<pre>".print_r($this->logInfo, true)."</pre>";
            print $this->lineBreak."[---------------------END DEBUG-----------------------------]".$this->lineBreak;

            return;
        }
        
        curl_setopt( $this->connection, CURLOPT_URL, $requestURL );
        curl_setopt( $this->connection, CURLOPT_HTTPHEADER, $httpHeaders );

        $responseBody = curl_exec( $this->connection );
        $info = curl_getinfo($this->connection);
        $this->logRequest($responseBody, $requestBody, $info);
        if(!curl_errno( $this->connection)) // If there is no error
        {
            if($info['http_code'] === $successHttpCode) {
                return $responseBody;
            }
            else {
                return null;
            }
        }
        else{
            return null;
        }
    }

    /*
     * Make a request using HTTP GET
     */
    public function doRequest($requestURL, $nonOAuthHeader = array(), $successHttpCode = 200) {
        return $this->sendRequest( "GET", trim($requestURL), $nonOAuthHeader, "", $successHttpCode );
    }

    /*
     * Make a request using HTTP Post
     */
    public function postRequest($requestURL, $requestBody = "", $nonOAuthHeader = array(), $successHttpCode = 201){
        return $this->sendRequest( "POST", trim($requestURL), $nonOAuthHeader, $requestBody, $successHttpCode );
    }

    /*
    * Make a request using HTTP PUT
    */
    public function putRequest($requestURL, $requestBody = "", $nonOAuthHeader = array(), $successHttpCode = 200){
        return $this->sendRequest( "PUT", trim($requestURL), $nonOAuthHeader, $requestBody, $successHttpCode );
    }

    /**
     *	Get a Request Token from the Service Provider.
     */
    public function getRequestToken() {
        $requestURL	= sprintf( "%s%s", $this->baseUrl, $this->requesttoken_path );

        $requestBody	= $this->sendRequest( "POST", $requestURL,  array(), "", 200  );

        preg_match( "~oauth_token\=([^\&]+)\&oauth_token_secret\=([^\&]+)~i", $requestBody, $tokens );
        if( !isset( $tokens[1] ) || !isset( $tokens[2] ) ) {
            return false;
        }

        $this->requestToken = $tokens[1] ;
        $this->tokenSecret = $tokens[2] ;
        if(strlen($this->requestToken)>0 && strlen($this->tokenSecret)>0) {
            return true;
        }
        return false;
    }

    /**
     *	Get an Access Token from the Service Provider.
     *  @param		oauthToken		The authorized request token. This token
     *                              is returned by the service provider when the user authenticates
     *                              on the service provider side. Use this request token to request a Access token
     */
    public function getAccessToken($oauthToken, $tokenSecret) {

        $this->requestToken = $oauthToken;
        $this->tokenSecret = $tokenSecret;

        $requestURL	= sprintf( "%s%s", $this->baseUrl, $this->accesstoken_path );

        curl_setopt( $this->connection, CURLOPT_NOBODY, true );
        
        $requestBody	= $this->sendRequest( "POST", $requestURL,  array(), "", 200  );

        preg_match( "~oauth_token\=([^\&]+)\&oauth_token_secret\=([^\&]+)~i", $requestBody, $tokens );
        if( !isset( $tokens[1] ) || !isset( $tokens[2] ) ) {
            return false;
        }

        $this->requestToken = $tokens[1] ;
        $this->tokenSecret = $tokens[2] ;

        return true;
    }

    /**
     * Gets the Request Token and Token Secret and then
     *	Redirect the client's browser to the Service Provider's Authentication page.
     */
    public function authenticateUser() {
        // First step is to get the Request Token (oauth_token)
        $this->getRequestToken();
        // Using the oauth_token take the user to Service Provider’s login screen.
        // Also provide a “callback” which the url to which the service provider redirects after the credentials are authenticated at the service provider side.
        if(CTCI_F1AppConfig::$includeRequestSecretInUrl) {
            $parts = parse_url($this->callbackUrl);
            $query = $parts['query'];
            if(strlen($query)>0) {
                $this->callbackUrl = $this->callbackUrl.'&oauth_token_secret='.$this->getTokenSecret();
            } else {
                $this->callbackUrl = $this->callbackUrl.'?oauth_token_secret='.$this->getTokenSecret();
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

    /*******************START- PRIVATE UTILITY FUNCTIONS ***************/
    /**
     *	Create a random "nonce" for every oAuth Request.
     */
    private function getOAuthNonce() {
        return md5( microtime() . rand( 500, 1000 ) );
    }

    /*
     * Builds OAuthHeader to be sent in a Request Token request. This method is used
     * to created the "Authorization" Header
     */
    private function buildOAuthHeader ( $oAuthOptions ) {
        $requestValues		= array();

        foreach( $oAuthOptions as $oAuthKey => $oAuthValue ) {
            if( substr( $oAuthKey, 0, 6 ) != "oauth_" )
            continue;

            if( is_array( $oAuthValue ) ) {
                foreach( $oAuthValue as $valueKey => $value ) {
                    $requestValues[]	= sprintf( "%s=%s", $valueKey, rawurlencode( utf8_encode( $value ) ) );
                }
            } else {
                $requestValues[]	= sprintf( "%s=%s", $oAuthKey, rawurlencode( utf8_encode( $oAuthValue ) ) );
            }
        }

        $requestValues		= implode( ",", $requestValues );

        return $requestValues;
    }

    /*
     * Returns a string of the format Authorization: <auth_string>
     * @param tokenType: Type of token 0==request token. > 0 Access token and other requests
     */
    private function getOAuthHeader ($httpMethod, $requestURL, $tokenType = 1) {
        $oAuthHeaderValues	= array(
            "oauth_consumer_key"		=> $this->consumerKey,
            "oauth_nonce"				=> $this->getOAuthNonce(),
            "oauth_signature_method"	=> "HMAC-SHA1",
            "oauth_timestamp"			=> mktime(),
            "oauth_version"				=> "1.0"
        );

        /*
        if($tokenType > 0) // Its not a request Request Token
        $oAuthHeaderValues["oauth_token"] = $this->requestToken;
        */

        if(strlen($this->requestToken)>0) {
            $oAuthHeaderValues["oauth_token"] = $this->requestToken;
        }

        $oAuthHeaderValues["oauth_signature"] = CTCI_RequestSigner::buildSignature($this->consumerSecret, $this->tokenSecret, $httpMethod, $requestURL, $oAuthHeaderValues, $this->logInfo);

        $oauthHeader = $this->buildOAuthHeader( $oAuthHeaderValues );

        return sprintf( "Authorization: %s", $oauthHeader);
    }

    private function logRequest($responseBody, $requestBody = "", $transferInfo = array()) {
        // The request string sent
        $requestString = curl_getinfo($this->connection, CURLINFO_HEADER_OUT);
        

        $debugArray = array();
        $requestArray = array();
        $responseArray = array();

        $requestArray['request_body'] = $requestBody;
        $requestArray['CURLINFO_HEADER_OUT'] = $requestString;

        $responseArray['RESPONSE_HEADERS'] = $this->responseHeaders;
        $responseArray['response_body'] = $responseBody;

        $debugArray['GET_INFO'] = $transferInfo;
        $debugArray['request'] = $requestArray;
        $debugArray['response'] = $responseArray;

        // $this->logInfo = $debugArray;
        $this->logInfo = array_merge($this->logInfo, $debugArray);

        if(CTCI_F1AppConfig::$debug){
            print $this->lineBreak."[--------------------BEGIN DEBUG----------------------------]".$this->lineBreak;
            print "<pre>".print_r($this->logInfo, true)."</pre>";
            print $this->lineBreak."[---------------------END DEBUG-----------------------------]".$this->lineBreak;
        }
    }

    /*
     *
     * Callback function to parse Response Headers
     * This function will be called for each response header parsed
     * @ch cURL connection object
     * @header The header value being parsed
     */
    private function readHeader($ch, $header) {
        $length = strlen($header);
        $this->responseHeaders[] = $header;
        return $length;
	}
    /*******************END- PRIVATE UTILITY FUNCTIONS ***************/
}
?>
