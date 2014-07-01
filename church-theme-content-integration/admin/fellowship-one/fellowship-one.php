<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 26/03/14
 * Time: 1:45 PM
 */

// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

require_once dirname( __FILE__ ) . '/../class-data-provider.php';
require_once dirname( __FILE__ ) . '/interface-f1-api-settings.php';
require_once dirname( __FILE__ ) . '/interface-f1-people-sync-settings.php';
require_once dirname( __FILE__ ) . '/OAuth/class-f1-oauth-client.php';
require_once dirname( __FILE__ ) . '/class-f1-people-data-provider.php';
require_once dirname( __FILE__ ) . '/../class-session.php';
require_once dirname( __FILE__ ) . '/../class-wpal.php';
require_once dirname( __FILE__ ) . '/../class-http-variables-manager.php';

class CTCI_Fellowship_One extends CTCI_DataProvider implements CTCI_F1APISettingsInterface, CTCI_F1PeopleSyncSettingsInterface {

    protected $debugMode = false;

	/** @var CTCI_WPALInterface */
	protected $wpal;
	/** @var CTCI_CTCISession */
	protected $session;
	/** @var CTCI_HTTPVariablesManager */
	protected $httpVarManager;
	/** @var CTCI_HtmlHelperInterface */
	protected $htmlHelper;

	protected $configFieldsBaseName = null;
	protected $peopleSyncEnableFieldName;

	protected $nameFormatOptions = array();

    // oauth authentication data
    protected $oauthStatus = null;
    protected $oauthMessage = null;

	/**
	 * @var CTCI_F1OAuthClientInterface
	 */
	protected $authClient = null;

	protected $peopleDataProvider = null;

	/**
	 * F1 API Settings
	 */
	protected $authMode;
	protected $consumerKey;
	protected $consumerSecret;
	protected $username;
	protected $password;
	protected $serverURL;

	/**
	 * People Sync settings
	 */
	protected $peopleLists;
	protected $syncPeopleGroups;
	protected $peopleNameFormat;
	protected $syncPersonPosition;
	protected $personPositionAttribute;
	protected $syncPersonPhone;
	protected $syncPersonEmail;
	protected $syncPersonFacebookURL;
	protected $syncPersonTwitterURL;
	protected $syncPersonLinkedInURL;

	public function __construct() {
        $this->debugMode = false;
		$this->session = null;
		$this->wpal = null;
		$this->nameFormatOptions = array(
			'T. FQ M L S' => 'Mr. Johnathan "John" Edward Doe II',
			'T. FQ L S' => 'Mr. Johnathan "John" Doe II',
			'T. F M L S' => 'Mr. Johnathan Edward Doe II',
			'T. F J. L S' => 'Mr. Johnathan E. Doe II',
			'T. F L S' => 'Mr. Johnathan Doe II',
			'T. G J. L S' => 'Mr. John E. Doe II',
			'T. G L S' => 'Mr. John Doe II',
			'T. I. L S' => 'Mr. J. Doe II',
			'T. L S' => 'Mr. Doe II',
			'T. L S, G' => 'Mr. Doe II, John',
			'T. L S, F' => 'Mr. Doe II, Johnathan',
			'T. L S, FQ' => 'Mr. Doe II, Johnathan "John"',
			'FQ M L S' => 'Johnathan "John" Edward Doe II',
			'FQ L S' => 'Johnathan "John" Doe II',
			'F M L S' => 'Johnathan Edward Doe II',
			'F J. L S' => 'Johnathan E. Doe II',
			'F L S' => 'Johnathan Doe II',
			'G J. L S' => 'John E. Doe II',
			'G L S' => 'John Doe II',
			'I L S' => 'J. Doe II',
			'G K.' => 'John D.',
			'G' => 'John',
			'K' => 'D',
			'K, G' => 'D, John',
			'L' => 'Doe',
			'L S' => 'Doe II',
			'L S, I.' => 'Doe II, J.',
			'L S, G' => 'Doe II, John',
			'L S, G J.' => 'Doe II, John E.',
			'L S, F' => 'Doe II, Johnathan',
			'L S, F J.' => 'Doe II, Johnathan E.',
			'L S, FQ' => 'Doe II, Johnathan "John"',
			'L S, FQ J.' => 'Doe II, Johnathan "John" E.',
		);
    }

    public function setDebugMode( $mode = true ) {
        $this->debugMode = $mode;
    }

	public function setWPAL( CTCI_WPALInterface $wpal ) {
		$this->wpal = $wpal;
	}

	/**
	 * @return string   A unique tag for this provider. Should only contain letters, numbers, or underscore.
	 */
	public function getTag() {
		return 'f1';
	}

	/**
	 * @return string   A human readable name for the provider.
	 */
	public function getHumanReadableName() {
		return __( 'Fellowship One', Church_Theme_Content_Integration::$TEXT_DOMAIN );
	}

	/**
	 * Return the file paths of php files to be included for this data provider.  The file paths should be relative
	 * to the data provider's root folder.
	 *
	 * @param string $type Either 'always' - return include files that should always be loaded.
	 *                          'admin' - for include files to be loaded in admin pages only (recommended).
	 *                          'frontend' - for files to be included in frontend pages only.
	 * @return array            An array of file names or paths, depending on $type
	 */
	public function getIncludes( $type ) {
		switch ( $type ) {
			case 'admin':
				return array(
					'interface-f1-api-settings.php',
					'interface-f1-people-sync-settings.php',
					'class-f1-people-data-provider.php',
					'OAuth/interface-f1-oauth-client.php',
					'OAuth/class-f1-api-util.php',
					'OAuth/class-f1-app-config.php',
					'OAuth/class-f1-oauth-client.php',
					'OAuth/class-request-signer.php',
				);
			default:
				return array();
		}
	}

	public function getSettingsGroupName() {
		return 'ctci_f1_options';
	}

	protected function getSettingsPageName() {
		return 'ctci_f1_options_page';
	}

	public function initOnLoad(
		CTCI_Session $session, CTCI_HTTPVariablesManagerInterface $httpVarManager, CTCI_HtmlHelperInterface $htmlHelper
	) {
		$this->session = $session;
		$this->httpVarManager = $httpVarManager;
		$this->htmlHelper = $htmlHelper;

		if ( $this->wpal !== null ) {
			$options = $this->wpal->getOption( $this->getSettingsGroupName() );
		} else {
			$options = get_option( $this->getSettingsGroupName() );
		}

        if ( false === $options ) {
			$this->authMode = $this->getDefaultSetting('auth_mode');
			$this->consumerKey = $this->getDefaultSetting('api_key');
			$this->consumerSecret = $this->getDefaultSetting('api_secret');
			$this->serverURL = $this->getDefaultSetting('api_url');
			if ( $this->authMode === CTCI_F1OAuthClient::CREDENTIALS ) {
				$this->username = $this->getDefaultSetting('username');
				$this->password = $this->getDefaultSetting('password');
			}
		} else {
            if ( $options['auth_mode'] == CTCI_F1OAuthClient::CREDENTIALS ) {
                $this->authMode = CTCI_F1OAuthClient::CREDENTIALS;
            } else {
                $this->authMode = CTCI_F1OAuthClient::OAUTH;
            }
            $this->consumerKey = $options['api_key'];
            $this->consumerSecret = $options['api_secret'];
            $this->serverURL = $options['api_url'];
            if ( $this->authMode === CTCI_F1OAuthClient::CREDENTIALS ) {
                $this->username = $options['username'];
                $this->password = $options['password'];
            }
        }

		$this->authClient = new CTCI_F1OAuthClient( $this );

		if ( $this->authMode == CTCI_F1OAuthClient::OAUTH ) {
			if ( $this->wpal != null ) {
				$this->authClient->setCallbackURL( $this->wpal->getCurrentAdminPageURL() );
			} else {
				$this->authClient->setCallbackURL(
					admin_url( "admin.php?page=" . Church_Theme_Content_Integration::$RUN_PAGE )
				);
			}

            // for oauth authentication, we may need to redirect, so we need a function hooked
            // into wordpress before any output is sent
            add_action( 'init', array( $this, 'handleAuthentication' ) );
            // see http://stackoverflow.com/questions/6903318/multiple-ajax-requests-delay-each-other
            // for why this is here, we only need the session for showing the sync button logic
            if ( ! $this->isAJAXRequest() ) {
                add_action( 'init', array( $this, 'startSession') );
            }
		}
    }

    protected function isAJAXRequest() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

	protected function registerSectionsAndFields() {
		$this->addSettingsSection(
			'ctci_f1_credentials',
			__( 'Credentials', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'credentialsSectionCallback'
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'auth_mode',
			__( 'Authentication Method', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displaySelectField',
			array(
				'options' => array(
					'3' => __( 'OAuth (3rd Party)', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
					'2' => __( 'Credentials Based (2nd Party)', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
				)
			)
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'api_url',
			__( 'API URL', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayTextField',
			array(
				'size' => '40'
			)
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'api_key',
			__( 'API Consumer Key', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayTextField',
			array(
				'size' => '6'
			)
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'api_secret',
			__( 'API Consumer Secret', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayTextField',
			array(
				'size' => '40'
			)
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'username',
			__( 'Username (2nd Party only!)', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayTextField'
		);
		$this->addSettingsField(
			'ctci_f1_credentials',
			'password',
			__( 'Password (2nd Party only!)', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayPasswordField'
		);
		$this->addSettingsSection(
			'ctci_f1_people_sync_settings',
			__( 'People Sync Settings', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'peopleSyncSectionCallback'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'people_lists',
			__( 'People Lists to Sync', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayTextAreaField',
			array(
				'rows' => '10',
				'cols' => '30'
			)
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_people_groups',
			__( 'Sync Lists to Groups?', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'name_format',
			__( 'Name Format', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displaySelectField',
			array(
				'options' => $this->nameFormatOptions
			)
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_position',
			__( 'Sync Position?', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'position_attribute',
			__( 'Position Attribute Group', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayTextField',
			array(
				'maxlength' => '50',
				'size' => '50'
			)
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_phone',
			__( 'Sync Phone?', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_email',
			__( 'Sync Email?', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_facebook',
			__( 'Sync Facebook URL?', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_twitter',
			__( 'Sync Twitter URL?', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayCheckBoxField'
		);
		$this->addSettingsField(
			'ctci_f1_people_sync_settings',
			'sync_linkedin',
			__( 'Sync LinkedIn URL?', Church_Theme_Content_Integration::$TEXT_DOMAIN ),
			'displayCheckBoxField'
		);
	}

	public function credentialsSectionCallback() {}

	public function peopleSyncSectionCallback() {}

	public function loadDefaultSettings() {
		$option = get_option( $this->getSettingsGroupName() );

		if ( ! is_array( $option ) ) {
			add_option( $this->getSettingsGroupName(), array(
				'auth_mode' => $this->getDefaultSetting('auth_mode'),
				'api_url' => $this->getDefaultSetting( 'api_url' ),
				'api_key' => $this->getDefaultSetting( 'api_key' ),
				'api_secret' => $this->getDefaultSetting( 'api_secret' ),
				'username' => $this->getDefaultSetting( 'username' ),
				'password' => $this->getDefaultSetting( 'password' ),
				'people_lists' => $this->getDefaultSetting( 'people_lists' ),
				'sync_people_groups' => $this->getDefaultSetting( 'sync_people_groups' ),
				'name_format' => $this->getDefaultSetting( 'name_format' ),
				'sync_position' => $this->getDefaultSetting( 'sync_position' ),
				'position_attribute' => $this->getDefaultSetting( 'position_attribute' ),
				'sync_phone' => $this->getDefaultSetting( 'sync_phone' ),
				'sync_email' => $this->getDefaultSetting( 'sync_email' ),
				'sync_facebook' => $this->getDefaultSetting( 'sync_facebook' ),
				'sync_twitter' => $this->getDefaultSetting( 'sync_twitter' ),
				'sync_linkedin' => $this->getDefaultSetting( 'sync_linkedin' ),
			));
		}
	}

	protected function getDefaultSetting( $setting ) {
		switch( $setting ) {
			case 'auth_mode': return '3';
			case 'api_url': return '';
			case 'api_key': return '';
			case 'api_secret': return '';
			case 'username': return '';
			case 'password': return '';
			case 'people_lists': return '';
			case 'sync_people_groups': return 'T';
			case 'name_format': return 'F L S';
			case 'sync_position': return 'F';
			case 'position_attribute': return '';
			case 'sync_phone': return 'T';
			case 'sync_email': return 'T';
			case 'sync_facebook': return 'T';
			case 'sync_twitter': return 'T';
			case 'sync_linkedin': return 'T';
			default: return null;
		}
	}

	public function validateSettings( $settings ) {
		$newInput = array();
		$newInput['auth_mode'] = trim( $settings['auth_mode'] );
		if ( $newInput['auth_mode'] != '2' && $newInput['auth_mode'] != '3' ) {
			$newInput['auth_mode'] = $this->getDefaultSetting('auth_mode');
		}
		$newInput['api_url'] = trim( $settings['api_url'] );
		// credit: https://gist.github.com/dperini/729294
		if ( ! preg_match(
			'%^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?$%iu',
			$newInput['api_url']
		) ) {
			$newInput['api_url'] = $this->getDefaultSetting('api_url');
		}
		$newInput['api_key'] = trim( $settings['api_key'] );
		if ( preg_match( '/\D/', $newInput['api_key'] ) ) {
			$newInput['api_key'] = $this->getDefaultSetting('api_key');
		}
		$newInput['api_secret'] = trim( $settings['api_secret'] );
		if ( preg_match( '/[^0-9a-f-]/i', $newInput['api_secret'] ) ) {
			$newInput['api_secret'] = $this->getDefaultSetting('api_secret');
		}
		$newInput['username'] = trim( $settings['username'] );
		if ( ! preg_match( '/^[\w]+$/', $newInput['username'] ) ) {
			$newInput['username'] = $this->getDefaultSetting('username');
		}
		$newInput['password'] = trim( $settings['password'] );
		if ( ! preg_match( '/^[\w;:,.\?!@#$%\^&\*\(\)-]+$/', $newInput['password'] ) ) {
			$newInput['password'] = $this->getDefaultSetting('password');
		}
		$newInput['people_lists'] = trim( $settings['people_lists'] );
		// is this needed? not sure what else to validate for
		$lines = explode( "\r\n", $newInput['people_lists'] );
		$changed = false;
		for ( $i = 0; $i < count($lines); $i++) {
			if ( strlen( $lines[ $i ] ) > 100 ) {
				$lines[ $i ] = substr( $lines[ $i ], 0, 100 );
				$changed = true;
			}
		}
		if ( $changed ) {
			$newInput['people_lists'] = implode( "\r\n", $lines );
		}
		if ( ! isset( $this->nameFormatOptions[ $settings['name_format'] ] ) ) {
			$newInput['name_format'] = $this->getDefaultSetting('name_format');
		} else {
			$newInput['name_format'] = trim( $settings['name_format'] );
		}
		$newInput['sync_people_groups'] = trim( $settings['sync_people_groups'] );
		if ( 'T' !== $newInput['sync_people_groups'] && 'F' !== $newInput['sync_people_groups'] ) {
			$newInput['sync_people_groups'] = $this->getDefaultSetting('sync_people_groups');
		}
		$newInput['sync_position'] = trim( $settings['sync_position'] );
		if ( 'T' !== $newInput['sync_position'] && 'F' !== $newInput['sync_position'] ) {
			$newInput['sync_position'] = $this->getDefaultSetting('sync_position');
		}
		$newInput['position_attribute'] = trim( $settings['position_attribute'] );
		if ( strlen( $newInput['position_attribute'] ) > 50 ) {
			$newInput['position_attribute'] = substr( $newInput['position_attribute'], 0, 50 );
		}
		$newInput['sync_phone'] = trim( $settings['sync_phone'] );
		if ( 'T' !== $newInput['sync_phone'] && 'F' !== $newInput['sync_phone'] ) {
			$newInput['sync_phone'] = $this->getDefaultSetting('sync_phone');
		}
		$newInput['sync_email'] = trim( $settings['sync_email'] );
		if ( 'T' !== $newInput['sync_email'] && 'F' !== $newInput['sync_email'] ) {
			$newInput['sync_email'] = $this->getDefaultSetting('sync_email');
		}
		$newInput['sync_facebook'] = trim( $settings['sync_facebook'] );
		if ( 'T' !== $newInput['sync_facebook'] && 'F' !== $newInput['sync_facebook'] ) {
			$newInput['sync_facebook'] = $this->getDefaultSetting('sync_facebook');
		}
		$newInput['sync_twitter'] = trim( $settings['sync_twitter'] );
		if ( 'T' !== $newInput['sync_twitter'] && 'F' !== $newInput['sync_twitter'] ) {
			$newInput['sync_twitter'] = $this->getDefaultSetting('sync_twitter');
		}
		$newInput['sync_linkedin'] = trim( $settings['sync_linkedin'] );
		if ( 'T' !== $newInput['sync_linkedin'] && 'F' !== $newInput['sync_linkedin'] ) {
			$newInput['sync_linkedin'] = $this->getDefaultSetting('sync_linkedin');
		}
		return $newInput;
	}

	public function getRunButtonHandlerType() {
		if ( $this->authMode === CTCI_F1OAuthClient::OAUTH ) {
			return self::RUNBUTTON_CUSTOM;
		} else {
			return self::RUNBUTTON_AJAX;
		}
	}

    /**
     * Called on wp init hook
     */
    public function startSession() {
        $this->session->start();
    }

    /**
     * Called on wp init hook
     */
    public function handleAuthentication() {
        // need to repeat this for other operations if supported
        $operationTag = CTCI_PeopleSync::getTag();
        $authActionValue = "auth_f1_{$operationTag}";

        if ( $this->httpVarManager->hasPostVar('ctci_action') ) {
            // handle any form submission for this button
            if ( $this->httpVarManager->getPostVar('ctci_action') === $authActionValue ) {

                try {

                    // if this succeeds, it redirects browser to service provider login page
                    // this needs to occur sufficiently early in the WP process to avoid headers
                    // already sent issue
                    $this->oauthStatus = $this->authClient->authenticate();
                    if ( $this->oauthStatus === true ) {
                        $this->callDie();
                    }

                } catch ( Exception $e ) {
                    $this->oauthStatus = false;
                    $this->oauthMessage = $this->authenticateExceptionMessage( $e );
                }
            }
        }
        //echo 'handleAuth';
    }

    protected function callDie() {
        die();
    }

    protected function authenticateExceptionMessage( Exception $e ) {
        try {
            throw $e;
        } catch ( CTCI_F1APIRequestException $e ) {
            if ( ! $this->debugMode ) {
                return __(
                    'Incorrect server response attempting to retrieve request tokens for authentication with the service provider. This may indicate an incorrect API URL setting. Set debug mode option for more information.',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                );
            } else {
                return sprintf( __(
                        'Incorrect server response attempting to retrieve request tokens for authentication with the service provider. This may indicate an incorrect API URL setting. HTTP Response Code: %d. Request URL: %s. Response Body: %s.',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getHttpCode(), $e->getRequestURL(), $e->getResponseBody()
                );
            }
        } catch ( CTCI_CURLException $e ) {
            if ( $e->getCode() === 6 ) {
                if ( ! $this->debugMode ) {
                    return __(
                        'Failed to resolve Fellowship One server. This may be caused by your internet connection or the server being down.',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    );
                } else {
                    return sprintf( __(
                        'Failed to resolve Fellowship One server. This may be caused by your internet connection or the server being down. cURL Error No: 6. Exception details: %s',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e );
                }
            } else {
                if ( ! $this->debugMode ) {
                    return sprintf( __(
                        'Authentication has failed. The cURL library has reported an error with error code: %d',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getCode() );
                } else {
                    return sprintf( __(
                        'Authentication has failed. The cURL library has reported an error with error code: %d. Exception details: %s',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getCode(), $e );
                }
            }
        } catch ( Exception $e ) {
            if ( ! $this->debugMode ) {
                return sprintf( __(
                    'An unexpected error occurred during authentication. Message: %s',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                ), $e->getMessage() );
            } else {
                return sprintf( __(
                    'An unexpected error occurred during authentication. Message: %s. Exception details: %s',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                ), $e->getMessage(), $e );
            }
        }
    }

	public function showSyncButtonFor( CTCI_OperationInterface $operation, $enabled = true ) {

		$operationTag = $operation->getTag();
		$authActionValue = "auth_f1_{$operationTag}";
		$authName = "ctci_f1_$authActionValue";
		$authId = $authName;
		/* translators: the label for the authenticate button(s) */
		$authButtonTitle = sprintf( __(
				'Authenticate with %1$s (%2$s)',
				Church_Theme_Content_Integration::$TEXT_DOMAIN
			), $this->getHumanReadableName(), $operation->getHumanReadableName()
		);

        if ( $this->oauthStatus === false ) { // attempted oauth authentication has failed
            $this->htmlHelper->showActionButton( $authActionValue, $authName, $authId, $authButtonTitle, $enabled );
            if ( $this->oauthMessage !== null && is_string( $this->oauthMessage) && $this->oauthMessage !== '' ) {
                return $this->oauthMessage;
            } else {
                return __( 'Could not authenticate with the server.', Church_Theme_Content_Integration::$TEXT_DOMAIN );
            }
        } elseif ( $this->session->hasF1AccessToken() && $this->session->hasF1AccessTokenSecret() ) {
			// already authenticated, just show sync button
            // the session is started via a hook to init
			$this->htmlHelper->showAJAXRunButtonFor( $this, $operation, $enabled );
		} elseif ( $this->httpVarManager->hasGetVar('oauth_token') && $this->httpVarManager->hasGetVar('oauth_token_secret') ) {
			// callback after authenticating with service provider

			// Get the "authenticated" request token here. The Service provider will append this token to the query string when
			// redirecting the user's browser to the Callback page
			$oauth_token = $this->httpVarManager->getGetVar("oauth_token");
			// The is the token secret which you got when you requested the request_token
			// You should get this because you appended this token secret when you got redirected to the
			// Service Provider's login screen
			$token_secret = $this->httpVarManager->getGetVar("oauth_token_secret");

			/** @noinspection PhpUnusedLocalVariableInspection */
			$success = false;
			$message = '';
			try {
				$success = $this->authClient->retrieveAccessToken($oauth_token, $token_secret);
			} catch ( Exception $e ) {
				$message = $this->retrieveAccessTokenExceptionMessage( $e );
			}

			if ( $success ) {
				$access_token = $this->authClient->getAccessToken();
				$token_secret = $this->authClient->getAccessTokenSecret();
				//print "Access token: ".$access_token.", Token Secret: ".$token_secret.'<br/>';
				$this->session
                    ->setF1AccessToken( $access_token )
				    ->setF1AccessTokenSecret( $token_secret )
                // save to the session the settings used to authenticate, so we re-use them
                // while the session remains valid
                    ->setF1URL( $this->serverURL )
                    ->setF1ConsumerKey( $this->consumerKey )
                    ->setF1ConsumerSecret( $this->consumerSecret );

                $this->htmlHelper->showAJAXRunButtonFor( $this, $operation, $enabled );
			} else {
				$this->htmlHelper->showActionButton( $authActionValue, $authName, $authId, $authButtonTitle, $enabled );
				if ( $message !== '' ) {
					return $message;
				} else {
					return __( 'Failed to retrieve Access Tokens for authentication with the service provider.', Church_Theme_Content_Integration::$TEXT_DOMAIN );
				}
			}
		} else {
			$this->htmlHelper->showActionButton( $authActionValue, $authName, $authId, $authButtonTitle, $enabled );
		}

		return true;
	}

    protected function retrieveAccessTokenExceptionMessage( Exception $e ) {
        try {
            throw $e;
        } catch ( CTCI_F1APIRequestException $e ) {
            if ( ! $this->debugMode ) {
                return __(
                    'Incorrect server response attempting to retrieve access tokens for authentication with the service provider. This may indicate an incorrect API URL setting. Set debug mode option for more information.',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                );
            } else {
                return sprintf( __(
                        'Incorrect server response attempting to retrieve access tokens for authentication with the service provider. This may indicate an incorrect API URL setting. HTTP Response Code: %d. Request URL: %s. Response Body: %s.',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getHttpCode(), $e->getRequestURL(), $e->getResponseBody()
                );
            }
        } catch ( CTCI_CURLException $e ) {
            if ( $e->getCode() === 6 ) {
                if ( ! $this->debugMode ) {
                    return __(
                        'Failed to resolve Fellowship One server. This may be caused by your internet connection or the server being down.',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    );
                } else {
                    return sprintf( __(
                        'Failed to resolve Fellowship One server. This may be caused by your internet connection or the server being down. cURL Error No: 6. Exception details: %s',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e );
                }
            } else {
                if ( ! $this->debugMode ) {
                    return sprintf( __(
                        'Failed to retrieve Access Tokens. The cURL library has reported an error with error code: %d',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getCode() );
                } else {
                    return sprintf( __(
                        'Failed to retrieve Access Tokens. The cURL library has reported an error with error code: %d. Exception details: %s',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getCode(), $e );
                }
            }
        } catch ( Exception $e ) {
            if ( ! $this->debugMode ) {
                return sprintf( __(
                    'An unexpected error occurred attempting to retrieve Access Tokens. Message: %s',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                ), $e->getMessage() );
            } else {
                return sprintf( __(
                    'An unexpected error occurred attempting to retrieve Access Tokens. Message: %s. Exception details: %s',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                ), $e->getMessage(), $e );
            }
        }
    }

	public function initDataProviderForProcess( CTCI_StatusTrackerInterface $statusTracker ) {
		if ( $this->wpal !== null ) {
			$options = $this->wpal->getOption( $this->getSettingsGroupName() );
		} else {
			$options = get_option( $this->getSettingsGroupName() );
		}
		if ( false === $options ) {
			throw new Exception( 'Options for ' . $this->getHumanReadableName() . ' could not be retrieved during initialisation for module process.' );
		}

		if ( $this->authMode === CTCI_F1OAuthClient::OAUTH ) {
            // note that this is called within an ajax request, so this will block other
            // ajax requests running simultaneously that also access the session
			$this->session->start();
			if ( $this->session->hasF1AccessToken() && $this->session->hasF1AccessTokenSecret() ) {
				$this->authClient
					->setAccessToken( $this->session->getF1AccessToken() )
					->setAccessTokenSecret( $this->session->getF1AccessTokenSecret() );
                // conditionally overwrite the connection settings from the session variables if they exist
                // these variables should be set upon successful authentication, but we may still be able to
                // use the wordpress setting loaded earlier, as long as it hasn't been changed since authenticating
                if ( $this->session->hasF1URL() ) {
                    $this->serverURL = $this->session->getF1URL();
                    $this->authClient->setServerURL( $this->serverURL );
                }
                if ( $this->session->hasF1ConsumerKey() ) {
                    $this->consumerKey = $this->session->getF1ConsumerKey();
                    $this->authClient->setConsumerKey( $this->consumerKey );
                }
                if ( $this->session->hasF1ConsumerSecret() ) {
                    $this->consumerSecret = $this->session->getF1ConsumerSecret();
                    $this->authClient->setConsumerSecret( $this->consumerSecret );
                }
			} else {
				$statusTracker->error( 'Access tokens could not be accessed from session' );
				return false;
			}
		}

		$this->peopleLists = explode( "\r\n", $options['people_lists']);
		$this->syncPeopleGroups = $options['sync_people_groups'] === 'T';
		$this->peopleNameFormat = $options['name_format'];
		$this->syncPersonPosition = $options['sync_position'] === 'T';
		$this->personPositionAttribute = $options['position_attribute'];
		$this->syncPersonPhone = $options['sync_phone'] === 'T';
		$this->syncPersonEmail = $options['sync_email'] === 'T';
		$this->syncPersonFacebookURL = $options['sync_facebook'] === 'T';
		$this->syncPersonTwitterURL = $options['sync_twitter'] === 'T';
		$this->syncPersonLinkedInURL = $options['sync_linkedin'] === 'T';

		// NOTE: this must come AFTER setting the above, as the auth client relies on retrieving the set values
		// in it's constructor
		$this->peopleDataProvider = new CTCI_F1PeopleDataProvider( $this->authClient, $this, $statusTracker );

		return true;
	}

	public function authenticateForProcess() {
		// this method is called during module process
		// we only need to authenticate here if using credentials based method
		if ( $this->authMode === CTCI_F1OAuthClient::CREDENTIALS ) {
			if ( empty( $this->consumerKey ) ) {
				throw new CTCI_AuthenticationException( 'API Consumer Key setting does not have a value.' );
			}
			if ( empty( $this->consumerSecret ) ) {
				throw new CTCI_AuthenticationException( 'API Consumer Secret setting does not have a value.' );
			}
			if ( empty( $this->username ) ) {
				throw new CTCI_AuthenticationException( 'API Username setting does not have a value.' );
			}
			if ( empty( $this->password ) ) {
				throw new CTCI_AuthenticationException( 'API Password setting does not have a value.' );
			}
			if ( empty( $this->serverURL ) ) {
				throw new CTCI_AuthenticationException( 'API Server setting does not have a value.' );
			}
            try {
                $success = $this->authClient->authenticate();
            } catch ( Exception $e ) {
                return $this->credentialsAuthenticateExceptionMessage( $e );
            }
			if ( ! $success) {
				throw new CTCI_AuthenticationException( 'Could not retrieve Request Tokens.' );
				// T/ODO: remove
				// *** only for debugging!!! ***
				/*throw new CTCI_AuthenticationException(
					sprintf( 'Could not authenticate with credentials: %s %s %s %s %s',
						$this->serverURL, $this->consumerKey, $this->consumerSecret, $this->username, $this->password
					)
				);*/
			}
			return $success;
		} else {
			return true;
		}
	}

    protected function credentialsAuthenticateExceptionMessage( Exception $e ) {
        try {
            throw $e;
        } catch ( CTCI_F1APIRequestException $e ) {
            if ( ! $this->debugMode ) {
                return __(
                    'Incorrect server response attempting to retrieve access tokens for authentication with the service provider. This may indicate an incorrect API URL setting. Set debug mode option for more information.',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                );
            } else {
                return sprintf( __(
                        'Incorrect server response attempting to retrieve access tokens for authentication with the service provider. This may indicate an incorrect API URL setting. HTTP Response Code: %d. Request URL: %s. Response Body: %s.',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getHttpCode(), $e->getRequestURL(), $e->getResponseBody()
                );
            }
        } catch ( CTCI_CURLException $e ) {
            if ( $e->getCode() === 6 ) {
                if ( ! $this->debugMode ) {
                    return __(
                        'Failed to resolve Fellowship One server. This may be caused by your internet connection or the server being down.',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    );
                } else {
                    return sprintf( __(
                        'Failed to resolve Fellowship One server. This may be caused by your internet connection or the server being down. cURL Error No: 6. Exception details: %s',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e );
                }
            } else {
                if ( ! $this->debugMode ) {
                    return sprintf( __(
                        'The cURL library has reported an error with error code: %d',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getCode() );
                } else {
                    return sprintf( __(
                        'The cURL library has reported an error with error code: %d. Exception details: %s',
                        Church_Theme_Content_Integration::$TEXT_DOMAIN
                    ), $e->getCode(), $e );
                }
            }
        } catch ( Exception $e ) {
            if ( ! $this->debugMode ) {
                return sprintf( __(
                    'An unexpected error occurred during authentication. Message: %s',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                ), $e->getMessage() );
            } else {
                return sprintf( __(
                    'An unexpected error occurred during authentication. Message: %s. Exception details: %s',
                    Church_Theme_Content_Integration::$TEXT_DOMAIN
                ), $e->getMessage(), $e );
            }
        }
    }

	public function isDataProviderFor( $operation ) {
		switch ( $operation ) {
			case CTCI_PeopleSync::getTag():
				return true;
			default:
				return false;
		}
	}

	public function getDataProviderFor( $operation ) {
		switch ( $operation ) {
			case CTCI_PeopleSync::getTag():
				return $this->peopleDataProvider;
			default:
				return null;
		}
	}

	/******************************************************
	 *
	 * F1APISettings methods
	 *
	 *******************************************************/

	public function getAuthenticationMode() {
		return $this->authMode;
	}

	public function getF1ConsumerKey() {
		return $this->consumerKey;
	}

	public function getF1ConsumerSecret() {
		return $this->consumerSecret;
	}

	public function getF1Username() {
		return $this->username;
	}

	public function getF1Password() {
		return $this->password;
	}

	public function getF1ServerBaseURL() {
		return $this->serverURL;
	}

	/******************************************************
	 *
	 * F1PeopleSyncSettings methods
	 *
	 *******************************************************/

	public function getF1PeopleLists() {
		return $this->peopleLists;
	}

	public function f1SyncPeopleGroups() {
		return $this->syncPeopleGroups;
	}

	public function f1NameFormat() {
		return $this->peopleNameFormat;
	}

	public function f1SyncPersonPosition() {
		return $this->syncPersonPosition;
	}

	public function f1PersonPositionAttribute() {
		return $this->personPositionAttribute;
	}

	public function f1SyncPersonPhone() {
		return $this->syncPersonPhone;
	}

	public function f1SyncPersonEmail() {
		return $this->syncPersonEmail;
	}

	public function f1SyncPersonFacebookURL() {
		return $this->syncPersonFacebookURL;
	}

	public function f1SyncPersonTwitterURL() {
		return $this->syncPersonTwitterURL;
	}

	public function f1SyncPersonLinkedInURL() {
		return $this->syncPersonLinkedInURL;
	}
}