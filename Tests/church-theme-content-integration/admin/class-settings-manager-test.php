<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 3:31 PM
 */

require_once dirname( __FILE__ ) . '/../../church-theme-content-integration/CTCI_DBAL.php';
require_once dirname( __FILE__ ) . '/../../church-theme-content-integration/class-settings-manager.php';
require_once dirname( __FILE__ ) . '/../../church-theme-content-integration/F1/OAuth/class-f1-app-config.php';

class CTCISettingsManagerTest extends PHPUnit_Framework_TestCase {
	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $dbalMock;

	public function setUp() {
		$this->dbalMock = $this->getMockBuilder( 'CTCI_WPAL' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function setupDBALMock(
		$f1PeopleSyncEnabled = null,
		$f1ConsumerKey = null, $f1ConsumerSecret = null, $f1Username = null, $f1Password = null, $f1ChurchCode = null, $f1Mode = null,
		$f1SyncGroups = null, $f1PeopleLists = null
	) {
		$this->dbalMock
			->expects( $this->any() )
			->method( 'getOption' )
			->will( $this->returnValueMap( array(
				array( CTCI_SettingsManager::F1PEOPLESYNCENABLED_OPT, $f1PeopleSyncEnabled ),
				array( CTCI_SettingsManager::F1CONSUMERKEY_OPT, $f1ConsumerKey ),
				array( CTCI_SettingsManager::F1CONSUMERSECRET_OPT, $f1ConsumerSecret ),
				array( CTCI_SettingsManager::F1USERNAME_OPT, $f1Username ),
				array( CTCI_SettingsManager::F1PASSWORD_OPT, $f1Password ),
				array( CTCI_SettingsManager::F1CHURCHCODE_OPT, $f1ChurchCode ),
				array( CTCI_SettingsManager::F1MODE_OPT, $f1Mode ),
				array( CTCI_SettingsManager::F1SYNCGROUPS_OPT, $f1SyncGroups ),
				array( CTCI_SettingsManager::F1PEOPLELISTS_OPT, $f1PeopleLists ),
			) ) );
	}

	public function testF1PeopleSyncEnabled() {
		/*$this->dbalMock->expects($this->any())
			->method('getOption')
			->with($this->equalTo(CTCI_SettingsManager::F1PEOPLESYNCENABLED_OPT))
			->will($this->returnValue(true));*/

		$this->setupDBALMock( true );

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertTrue( $sut->f1PeopleSyncEnabled() );
	}

	public function testF1PeopleSyncDisabled() {
		/*$this->dbalMock->expects($this->any())
			->method('getOption')
			->with($this->equalTo(CTCI_SettingsManager::F1PEOPLESYNCENABLED_OPT))
			->will($this->returnValue(false));*/

		$this->setupDBALMock( false );

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertFalse( $sut->f1PeopleSyncEnabled() );
	}

	/*
	 * For many of these tests, if the CTCI_F1AppConfig variable has a value, it should be used.
	 * If not, then use the value from the dbal (WPSetting).
	 */

	public function testF1ConsumerKeyFromWPSetting() {
		$this->setupDBALMock( true, '123' );
		CTCI_F1AppConfig::$consumer_key = '';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( '123', $sut->getF1ConsumerKey() );
	}

	public function testF1ConsumerKeyFromConfigFile() {
		$this->setupDBALMock( true, '123' );
		CTCI_F1AppConfig::$consumer_key = '999';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( '999', $sut->getF1ConsumerKey() );
	}

	public function testF1ConsumerKeyFromConfigFile2() {
		$this->setupDBALMock( true, null );
		CTCI_F1AppConfig::$consumer_key = '999';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( '999', $sut->getF1ConsumerKey() );
	}

	public function testF1ConsumerSecretFromWPSetting() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012' );
		CTCI_F1AppConfig::$consumer_secret = '';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( '12345678-1234-1234-1234-123456789012', $sut->getF1ConsumerSecret() );
	}

	public function testF1ConsumerSecretFromConfigFile() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012' );
		CTCI_F1AppConfig::$consumer_secret = '87654321-1234-1234-1234-123456789012';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( '87654321-1234-1234-1234-123456789012', $sut->getF1ConsumerSecret() );
	}

	public function testF1ConsumerSecretFromConfigFile2() {
		$this->setupDBALMock( true, '123', null );
		CTCI_F1AppConfig::$consumer_secret = '87654321-1234-1234-1234-123456789012';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( '87654321-1234-1234-1234-123456789012', $sut->getF1ConsumerSecret() );
	}

	public function testF1UsernameFromWPSetting() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012', 'myusername' );
		CTCI_F1AppConfig::$username = null;

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'myusername', $sut->getF1Username() );
	}

	public function testF1UsernameFromConfigFile() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012', 'myusername' );
		CTCI_F1AppConfig::$username = 'mysecretusername';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'mysecretusername', $sut->getF1Username() );
	}

	public function testF1UsernameFromConfigFile2() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012', null );
		CTCI_F1AppConfig::$username = 'mysecretusername';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'mysecretusername', $sut->getF1Username() );
	}

	public function testF1PasswordFromWPSetting() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword' );
		CTCI_F1AppConfig::$password = '';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'mypassword', $sut->getF1Password() );
	}

	public function testF1PasswordFromConfigFile() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword' );
		CTCI_F1AppConfig::$password = 'mysecretpassword';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'mysecretpassword', $sut->getF1Password() );
	}

	public function testF1PasswordFromConfigFile2() {
		$this->setupDBALMock( true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', '' );
		CTCI_F1AppConfig::$password = 'mysecretpassword';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'mysecretpassword', $sut->getF1Password() );
	}

	public function testF1ServerBaseURL_StagingFromWPSetting() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_STAGING
		);
		CTCI_F1AppConfig::$base_url = '';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'https://f1chco.staging.fellowshiponeapi.com', $sut->getF1ServerBaseURL() );
	}

	public function testF1ServerBaseURL_ProductionFromWPSetting() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_PRODUCTION
		);
		CTCI_F1AppConfig::$base_url = '';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		// need to check this is correct once I get a production key
		$this->assertEquals( 'https://f1chco.portal.fellowshiponeapi.com', $sut->getF1ServerBaseURL() );
	}

	public function testF1ServerBaseURL_StagingFromConfigFile() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_STAGING
		);
		CTCI_F1AppConfig::$base_url = 'https://mychco.staging.fellowshiponeapi.com';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( 'https://mychco.staging.fellowshiponeapi.com', $sut->getF1ServerBaseURL() );
	}

	public function testF1ServerBaseURL_ProductionFromConfigFile() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_PRODUCTION
		);
		CTCI_F1AppConfig::$base_url = 'https://mychco.portal.fellowshiponeapi.com';

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		// need to check this is correct once I get a production key
		$this->assertEquals( 'https://mychco.portal.fellowshiponeapi.com', $sut->getF1ServerBaseURL() );
	}

	public function testF1SyncGroups_True() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_PRODUCTION,
			true
		);

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertTrue( $sut->f1SyncGroups() );
	}

	public function testF1SyncGroups_False() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_PRODUCTION,
			false
		);

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertFalse( $sut->f1SyncGroups() );
	}

	public function testGetF1PeopleLists1() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_PRODUCTION,
			false, array( 'pl1' )
		);

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( array( 'pl1' ), $sut->getF1PeopleLists() );
	}

	public function testGetF1PeopleLists2() {
		$this->setupDBALMock(
			true, '123', '12345678-1234-1234-1234-123456789012', 'myusername', 'mypassword',
			'f1chco', CTCI_SettingsManager::F1MODE_OPTVALUE_PRODUCTION,
			false, array( 'pl1', 'pl2', 'pl3' )
		);

		$sut = new CTCI_SettingsManager( $this->dbalMock );

		$this->assertEquals( array( 'pl1', 'pl2', 'pl3' ), $sut->getF1PeopleLists() );
	}

}
 