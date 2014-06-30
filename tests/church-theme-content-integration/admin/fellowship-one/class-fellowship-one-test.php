<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 1/04/14
 * Time: 10:50 AM
 */

require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/fellowship-one/fellowship-one.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/class-people-sync.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/interface-people-data-provider.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/interface-status-tracker.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/class-status-tracker.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/class-wpal.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/class-html-helper.php';

// subclass the sut to override the die method so that it doesn't affect the tests
class FellowshipOneSUT extends CTCI_Fellowship_One {
    protected $dieCalled = false;
    protected function callDie() {
        $this->dieCalled = true;
    }
    public function wasDieCalled() {
        return $this->dieCalled;
    }
}

class CTCI_Fellowship_One_Test extends PHPUnit_Framework_TestCase {

	/** @var FellowshipOneSUT */
	protected $sut;

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $wpalMock;

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $httpVarMock;

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $htmlHelperMock;

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $sessionMock;

	/** @var CTCI_MemorySessionAdapter */
	protected $memSession;

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $authClientMock;

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $statusTrackerMock;

	public function setUp() {
		$this->sut = new FellowshipOneSUT();

		$this->wpalMock = $this->getMockBuilder('CTCI_WPAL')->disableOriginalConstructor()->getMock();
		$this->sut->setWPAL( $this->wpalMock );

		$this->memSession = new CTCI_MemorySessionAdapter();
		// by default don't replace any method
		$this->sessionMock = $this->getMock('CTCI_CTCISession', null, array( $this->memSession ));

		$this->httpVarMock = $this->getMock('CTCI_HTTPVariablesManager');

		$this->htmlHelperMock = $this->getMock('CTCI_HtmlHelper', array(), array( $this, 'get_run_module_key' ) );

		$this->statusTrackerMock = $this->getMockBuilder('CTCI_StatusTracker')->disableOriginalConstructor()->getMock();
	}

	protected function get_run_module_key( $providerTag, $operation ) {
		return "ctci_run_{$providerTag}_$operation";
	}

	protected function sutInitOnLoad() {
		$this->sut->initOnLoad( $this->sessionMock, $this->httpVarMock, $this->htmlHelperMock );
	}

	protected function injectOAuthClient() {
		$this->authClientMock = $this->getMock('CTCI_F1OAuthClient', array(), array( $this->sut ) );
		$reflect = new ReflectionObject( $this->sut );
		$authClientProp = $reflect->getProperty( 'authClient' );
		$authClientProp->setAccessible( true );
		$authClientProp->setValue( $this->sut, $this->authClientMock );
		$authClientProp->setAccessible( false );
	}

	public static function validateSettingsData() {
		return array(
			'default' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'good_auth_mode' => array(
				self::buildSettingsArray( '2', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '2', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_auth_mode' => array(
				self::buildSettingsArray( 'a', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_url' => array(
				self::buildSettingsArray( '2', 'https:/test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'F', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '2', '',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'F', 'F', 'T', 'F', 'T'
				)
			),
			'bad_key' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'12 34', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_key_2' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'12c', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_secret' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdeg-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_username' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', '<username>', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', '', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_password' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', '<Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', '', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'good_alt_name_format' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "T. FQ M L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "T. FQ M L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_name_format' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "<F L>",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_sync_people_groups' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'f',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_people_lists_too_long' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901\r\nList 2",
					"F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890\r\nList 2",
					"F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_position_attribute' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F',
					"List 1\r\nList 2", "F L S",
					'T','123456789012345678901234567890123456789012345678901', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F',
					"List 1\r\nList 2", "F L S",
					'T','12345678901234567890123456789012345678901234567890', 'T', 'F', 'T', 'F', 'T'
				)
			),
			// this essentially covers the other sync options which have the same logic
			'bad_sync_phone' => array(
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', '<bad code>', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '3', 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F',
					"List 1\r\nList 2", "F L S",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
		);
	}

	protected static function buildSettingsArray(
		$auth_mode, $api_url, $api_key, $api_secret, $username, $password, $sync_people_groups, $people_lists, $name_format,
		$sync_position, $position_attribute, $sync_phone, $sync_email, $sync_facebook, $sync_twitter, $sync_linkedin
	) {
		return array(
			'auth_mode' => $auth_mode,
			'api_url' => $api_url,
			'api_key' => $api_key,
			'api_secret' => $api_secret,
			'username' => $username,
			'password' => $password,
			'people_lists' => $people_lists,
			'name_format' => $name_format,
			'sync_people_groups' => $sync_people_groups,
			'sync_position' => $sync_position,
			'position_attribute' => $position_attribute,
			'sync_phone' => $sync_phone,
			'sync_email' => $sync_email,
			'sync_facebook' => $sync_facebook,
			'sync_twitter' => $sync_twitter,
			'sync_linkedin' => $sync_linkedin
		);
	}

	/**
	 * @dataProvider validateSettingsData
	 * @param $input
	 * @param $output
	 */
	public function testValidateSettings( $input, $output ) {

		$this->sutInitOnLoad();

		$actual = $this->sut->validateSettings( $input );

		$this->assertEquals( $output, $actual );
	}

	public static function isProviderForData() {
		return array(
			array( CTCI_PeopleSync::getTag(), true ),
			array( 'somethingelse', false ),
		);
	}

	/**
	 * @dataProvider isProviderForData
	 * @param $tag
	 * @param bool $result
	 */
	public function testIsProviderFor( $tag, $result = true ) {
		$this->sutInitOnLoad();
		$this->assertEquals( $result, $this->sut->isDataProviderFor( $tag ) );
	}

	public static function getDataProviderForData() {
		return array(
			array( CTCI_PeopleSync::getTag(), 'CTCI_PeopleDataProviderInterface' ),
			array( 'notaprovider', null )
		);
	}

	/**
	 * @dataProvider getDataProviderForData
	 * @param $tag
	 * @param null $instanceof
	 */
	public function testGetDataProviderFor( $tag, $instanceof = null ) {

		$this->memSession->set('ctci_f1_access_token', 1234567890);
		$this->memSession->set('ctci_f1_access_token_secret', 1234567890);

		$this->sutInitOnLoad();

		// must be called before getDataProvider
		$this->sut->initDataProviderForProcess( $this->statusTrackerMock );

		$actual = $this->sut->getDataProviderFor( $tag );

		if ( is_string( $instanceof ) ) {
			$this->assertInstanceOf( $instanceof, $actual );
		} else {
			$this->assertNull( $actual );
		}
	}

	public function testShowSyncButtonFor_AuthenticateSuccess() {

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', true),
			)));

		$this->httpVarMock->expects($this->any())
			->method('getPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', 'auth_f1_people_sync'),
			)));

		$this->authClientMock->expects($this->once())
			->method('authenticate')
			->will($this->returnValue(true));

		$this->htmlHelperMock->expects($this->never())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();

        $this->assertTrue( $this->sut->wasDieCalled() );
	}

	public function testShowSyncButtonFor_AuthenticateFailure() {

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', true),
			)));

		$this->httpVarMock->expects($this->any())
			->method('getPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', 'auth_f1_people_sync'),
			)));

		$this->authClientMock->expects($this->once())
			->method('authenticate')
			->will( $this->returnValue( false ) );

		$this->htmlHelperMock->expects($this->once())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();
		$return = $this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ) );

		// check that we get an error message
		$this->assertTrue( $return !== true && is_string( $return ) && $return !== '' );
        // die should only be called if authenticate returns true
        $this->assertFalse( $this->sut->wasDieCalled() );
	}

	public function testShowSyncButtonFor_AuthenticateException() {

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', true),
			)));

		$this->httpVarMock->expects($this->any())
			->method('getPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', 'auth_f1_people_sync'),
			)));

		$exception = new CTCI_F1APIRequestException('url', array('headers'), 404, 'body');

		$this->authClientMock->expects($this->once())
			->method('authenticate')
			->will( $this->throwException( $exception ) );

		$this->htmlHelperMock->expects($this->once())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();
		$return = $this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ) );

		// check that we get an error message
		$this->assertTrue( $return !== true && is_string( $return ) && $return !== '' );
        // die should only be called if authenticate returns true
        $this->assertFalse( $this->sut->wasDieCalled() );
	}

	public function testShowSyncButtonFor_AuthenticateForOtherOperation() {

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', true),
			)));

		$this->httpVarMock->expects($this->any())
			->method('getPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', 'auth_f1_not_people_sync'),
			)));

		$this->authClientMock->expects($this->never())
			->method('authenticate');

		$this->htmlHelperMock->expects($this->once())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();
		$return = $this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ) );

		$this->assertTrue( $return );
        // die should only be called if authenticate returns true
        $this->assertFalse( $this->sut->wasDieCalled() );
	}

	public function testShowSyncButtonFor_AuthenticatedInSession() {

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', false),
			)));

		$this->authClientMock->expects($this->never())
			->method('authenticate');

		$this->memSession->set('ctci_f1_access_token', 1234567890);
		$this->memSession->set('ctci_f1_access_token_secret', 1234567890);

		$this->htmlHelperMock->expects($this->never())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->once())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();
        $return = $this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ) );

        $this->assertTrue( $return );
        // die should only be called if authenticate returns true
        $this->assertFalse( $this->sut->wasDieCalled() );
	}

	public function testShowSyncButtonFor_AuthenticateCallback() {

		$this->sessionMock = $this->getMock( 'CTCI_CTCISession', array(), array( $this->memSession ) );

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', false),
			)));

		$this->authClientMock->expects($this->never())
			->method('authenticate');

		$this->httpVarMock->expects($this->any())
			->method('hasGetVar')
			->will( $this->returnValueMap(array(
				array( 'oauth_token', true ),
				array( 'oauth_token_secret', true )
			)));

		$oauth_token = 1234567890;
		$oauth_token_secret = 12345678901234567890;

		$this->httpVarMock->expects($this->any())
			->method('getGetVar')
			->will( $this->returnValueMap(array(
				array( 'oauth_token', $oauth_token ),
				array( 'oauth_token_secret', $oauth_token_secret )
			)));

		$this->authClientMock->expects( $this->once() )
			->method( 'retrieveAccessToken' )
			->with( $this->equalTo( $oauth_token ), $this->equalTo( $oauth_token_secret ) )
			->will( $this->returnValue( true ) );

		$access_token = '1234567890';
		$access_token_secret = '12345678-abcd-0123-abcdef12345678';

		$this->authClientMock->expects( $this->once() )
			->method( 'getAccessToken' )
			->will( $this->returnValue( $access_token ) );

		$this->authClientMock->expects( $this->once() )
			->method( 'getAccessTokenSecret' )
			->will( $this->returnValue( $access_token_secret ) );

	    $this->sessionMock->expects( $this->once() )
            ->method( 'setF1AccessToken' )
            ->with( $this->equalTo( $access_token ) )
            ->will( $this->returnSelf() );

        $this->sessionMock->expects( $this->once() )
            ->method( 'setF1AccessTokenSecret' )
            ->with( $this->equalTo( $access_token_secret ) )
            ->will( $this->returnSelf() );

        $this->sessionMock->expects( $this->once() )
            ->method( 'setF1ConsumerKey' )
            ->will( $this->returnSelf() );

        $this->sessionMock->expects( $this->once() )
            ->method( 'setF1ConsumerSecret' )
            ->will( $this->returnSelf() );

        $this->sessionMock->expects( $this->once() )
            ->method( 'setF1URL' )
            ->will( $this->returnSelf() );

		$this->htmlHelperMock->expects($this->never())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->once())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();
        $return = $this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ) );

        $this->assertTrue( $return );
        // die should only be called if authenticate returns true
        $this->assertFalse( $this->sut->wasDieCalled() );
		/*$this->assertTrue( $accessTokenSet, "access token not set" );
		$this->assertTrue( $accessTokenSecretSet, "access token secret not set" );*/
	}

	public function testShowSyncButtonFor_AuthenticateCallback_AccessTokenError() {

		$this->sessionMock = $this->getMock( 'CTCI_CTCISession', array('set'), array( $this->memSession ) );

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', false),
			)));

		$this->authClientMock->expects($this->never())
			->method('authenticate');

		$this->httpVarMock->expects($this->any())
			->method('hasGetVar')
			->will( $this->returnValueMap(array(
				array( 'oauth_token', true ),
				array( 'oauth_token_secret', true )
			)));

		$oauth_token = 1234567890;
		$oauth_token_secret = 12345678901234567890;

		$this->httpVarMock->expects($this->any())
			->method('getGetVar')
			->will( $this->returnValueMap(array(
				array( 'oauth_token', $oauth_token ),
				array( 'oauth_token_secret', $oauth_token_secret )
			)));

		$this->authClientMock->expects( $this->once() )
			->method( 'retrieveAccessToken' )
			->with( $this->equalTo( $oauth_token ), $this->equalTo( $oauth_token_secret ) )
			->will( $this->returnValue( false ) );

		$this->authClientMock->expects( $this->never() )
			->method( 'getAccessToken' );

		$this->authClientMock->expects( $this->never() )
			->method( 'getAccessTokenSecret' );

		$this->sessionMock->expects( $this->never() )
			->method( 'set' );

		$this->htmlHelperMock->expects($this->once())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();
        $return = $this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ) );

        // die should only be called if authenticate returns true
        $this->assertFalse( $this->sut->wasDieCalled() );
		// check that we get an error message
		$this->assertTrue( $return !== true && is_string( $return ) && $return !== '' );
	}

	public function testShowSyncButtonFor_AuthenticateCallback_AccessTokenException() {

		$this->sessionMock = $this->getMock( 'CTCI_CTCISession', array('set'), array( $this->memSession ) );

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', false),
			)));

		$this->authClientMock->expects($this->never())
			->method('authenticate');

		$this->httpVarMock->expects($this->any())
			->method('hasGetVar')
			->will( $this->returnValueMap(array(
				array( 'oauth_token', true ),
				array( 'oauth_token_secret', true )
			)));

		$oauth_token = 1234567890;
		$oauth_token_secret = 12345678901234567890;

		$this->httpVarMock->expects($this->any())
			->method('getGetVar')
			->will( $this->returnValueMap(array(
				array( 'oauth_token', $oauth_token ),
				array( 'oauth_token_secret', $oauth_token_secret )
			)));

		$exception = new CTCI_F1APIRequestException('url', array('headers'), 404, 'body' );

		$this->authClientMock->expects( $this->once() )
			->method( 'retrieveAccessToken' )
			->with( $this->equalTo( $oauth_token ), $this->equalTo( $oauth_token_secret ) )
			->will( $this->throwException( $exception ) );

		$this->authClientMock->expects( $this->never() )
			->method( 'getAccessToken' );

		$this->authClientMock->expects( $this->never() )
			->method( 'getAccessTokenSecret' );

		$this->sessionMock->expects( $this->never() )
			->method( 'set' );

		$this->htmlHelperMock->expects($this->once())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButtonFor');

        $this->sut->handleAuthentication();
        $return = $this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ) );

        // die should only be called if authenticate returns true
        $this->assertFalse( $this->sut->wasDieCalled() );
        // check that we get an error message
        $this->assertTrue( $return !== true && is_string( $return ) && $return !== '' );
	}

	public function testShowSyncButtonFor_Default() {

		$this->sessionMock = $this->getMock( 'CTCI_CTCISession', array('set'), array( $this->memSession ) );

		$this->sutInitOnLoad();

		$this->injectOAuthClient();

		$this->httpVarMock->expects($this->any())
			->method('hasPostVar')
			->will($this->returnValueMap(array(
				array('ctci_action', false),
			)));

		$this->authClientMock->expects($this->never())
			->method('authenticate');

		$this->httpVarMock->expects($this->any())
			->method('hasGetVar')
			->will( $this->returnValueMap(array(
				array( 'oauth_token', false ),
				array( 'oauth_token_secret', false )
			)));

		$this->authClientMock->expects( $this->never() )
			->method( 'retrieveAccessToken' );

		$this->authClientMock->expects( $this->never() )
			->method( 'getAccessToken' );

		$this->authClientMock->expects( $this->never() )
			->method( 'getAccessTokenSecret' );

		$this->sessionMock->expects( $this->never() )
			->method( 'set' );

		$this->statusTrackerMock->expects( $this->never() )
			->method( 'error' );

		$this->htmlHelperMock->expects($this->once())
			->method('showActionButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButton');

		$this->htmlHelperMock->expects($this->never())
			->method('showAJAXRunButtonFor');

		$this->sut->showSyncButtonFor( new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock ), $this->statusTrackerMock );

	}
}
