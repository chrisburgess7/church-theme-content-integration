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

class CTCI_Fellowship_One_Test extends PHPUnit_Framework_TestCase {

	/** @var CTCI_Fellowship_One */
	protected $sut;

	public function setUp() {
		$this->sut = new CTCI_Fellowship_One();
	}

	public static function validateSettingsData() {
		return array(
			'default' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_key' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'12 34', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_key_2' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'12c', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'', '12345678-1234-1234-123456789012', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_secret' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdeg-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_username' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', '<username>', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', '', 'Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_password' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', '<Pw1;:,.?!@#$%^&*()-', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', '', 'T', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_sync_people_groups' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 't', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F', "List 1\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_people_lists_too_long' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'T',
					"1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890\r\nList 2",
					'T','Church Position', 'T', 'F', 'T', 'F', 'T'
				)
			),
			'bad_position_attribute' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F', "List 1\r\nList 2",
					'T','123456789012345678901234567890123456789012345678901', 'T', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F', "List 1\r\nList 2",
					'T','12345678901234567890123456789012345678901234567890', 'T', 'F', 'T', 'F', 'T'
				)
			),
			// this essentially covers the other sync options which have the same logic
			'bad_sync_phone' => array(
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F', "List 1\r\nList 2",
					'T','Church Position', '<bad code>', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( 'https://test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F', "List 1\r\nList 2",
					'T','Church Position', 'F', 'F', 'T', 'F', 'T'
				)
			),
			'bad_url' => array(
				self::buildSettingsArray( 'https:/test.fellowshipone.com/',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F', "List 1\r\nList 2",
					'T','Church Position', 'F', 'F', 'T', 'F', 'T'
				),
				self::buildSettingsArray( '',
					'123', '12345678-90ab-cdef-1234567890ab', 'username', 'Pw1;:,.?!@#$%^&*()-', 'F', "List 1\r\nList 2",
					'T','Church Position', 'F', 'F', 'T', 'F', 'T'
				)
			)
		);
	}

	protected static function buildSettingsArray(
		$api_url, $api_key, $api_secret, $username, $password, $sync_people_groups, $people_lists,
		$sync_position, $position_attribute, $sync_phone, $sync_email, $sync_facebook, $sync_twitter, $sync_linkedin
	) {
		return array(
			'api_url' => $api_url,
			'api_key' => $api_key,
			'api_secret' => $api_secret,
			'username' => $username,
			'password' => $password,
			'sync_people_groups' => $sync_people_groups,
			'people_lists' => $people_lists,
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
		$this->assertEquals( $result, $this->sut->isProviderFor( $tag ) );
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
		// must be called before getDataProvider
		$this->sut->initDataProvider();

		$actual = $this->sut->getDataProviderFor( $tag );

		if ( is_string( $instanceof ) ) {
			$this->assertInstanceOf( $instanceof, $actual );
		} else {
			$this->assertNull( $actual );
		}
	}
}
 