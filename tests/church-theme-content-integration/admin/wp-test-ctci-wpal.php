<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 13/03/14
 * Time: 3:02 PM
 */

class WP_Test_CTCI_WPALTest extends WP_UnitTestCase {

	/** @var CTCI_WPAL */
	protected $sut;

	public function setUp() {
		parent::setUp();
		$this->sut = new CTCI_WPAL();
	}

	public function testCreateCTCGroup() {
		$group = new CTCI_PeopleGroup('f1', '12345', 'My group', 'My group description');

		$result = $this->sut->createCTCGroup( $group );

		// check the returned result
		$this->assertInstanceOf( 'CTCI_CTCGroup', $result );
		$this->assertEquals( 'My group', $result->getName() );
		$this->assertEquals( 'My group description', $result->getDescription() );
		// check the term in the db
		$termRecord = get_term( $result->id(), CTCI_WPAL::$ctcPersonGroupTaxonomy, ARRAY_A );
		$this->assertTrue( $termRecord !== false );
		$this->assertFalse( is_wp_error( $termRecord ) );
		$this->assertEquals( $termRecord['name'], 'My group' );
		$this->assertEquals( $termRecord['description'], 'My group description' );
	}

	public function testCreateCTCGroupException() {
		// empty name triggers exception
		$group = new CTCI_PeopleGroup('f1', '12345', '', 'My group description');
		$this->setExpectedException('CTCI_CreateCTCGroupException');
		try {
			$this->sut->createCTCGroup( $group );
		} catch (CTCI_CreateCTCGroupException $e) {
			$this->assertTrue( is_wp_error($e->getWPError() ) );
			throw $e;
		}
	}

	public function testAttachCTCGroup() {
		/** @var $wpdb wpdb */
		global $wpdb;
		// id of 25 matches the Elders group in the sample data
		$ctcGroup = new CTCI_CTCGroup( 25, 'CTC Group', '' );
		$group = new CTCI_PeopleGroup( 'f1', '37e21', 'My Group', '' );

		$response = $this->sut->attachCTCGroup( $ctcGroup, $group );

		$this->assertEquals( 'inserted', $response );

		$result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable .
			" WHERE term_id = 25 AND data_provider = 'f1' AND provider_group_id = '37e21'"
		);

		$this->assertNotNull( $result );
	}

	public function testAttachCTCGroupInsertException() {
		/** @var $wpdb PHPUnit_Framework_MockObject_MockObject */
		global $wpdb;
		$wpdbActual = $wpdb;
		$wpdb = $this->getMockBuilder('wpdb')->disableOriginalConstructor()->getMock();
		$wpdb->expects($this->any())
			->method('insert')
			->will($this->returnValue(false));
		$this->setExpectedException('CTCI_InsertCTCGroupAttachRecordException');
		// id of 25 matches the Elders group in the sample data
		$ctcGroup = new CTCI_CTCGroup( 25, 'CTC Group', '' );
		$group = new CTCI_PeopleGroup( 'f1', '37e21', 'My Group', '' );

		try {
			$this->sut->attachCTCGroup( $ctcGroup, $group );
		} catch (Exception $e) {
			// make sure to reset the global wpdb for other tests
			$wpdb = $wpdbActual;
			throw $e;
		}
		$wpdb = $wpdbActual;
	}

	public function testAttachCTCGroupAlreadyAttached() {
		/** @var $wpdb wpdb */
		global $wpdb;
		// id of 25 matches the Elders group in the sample data
		$ctcGroup = new CTCI_CTCGroup( 25, 'CTC Group', '' );
		$group = new CTCI_PeopleGroup( 'f1', '37e21', 'My Group', '' );

		// attach the first group
		$response = $this->sut->attachCTCGroup( $ctcGroup, $group );
		$this->assertEquals( 'inserted', $response );
		// check it's ok
		$result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable .
			" WHERE term_id = 25 AND data_provider = 'f1' AND provider_group_id = '37e21'"
		);
		$this->assertNotNull( $result );

		// attach a new group
		$group = new CTCI_PeopleGroup( 'f1', '18391', 'My Group', '' );

		$response = $this->sut->attachCTCGroup( $ctcGroup, $group );
		$this->assertEquals( 'updated', $response );

		$result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable .
			" WHERE term_id = 25 AND data_provider = 'f1' AND provider_group_id = '37e21'"
		);
		// check there is no row with the original attachment info
		$this->assertNull( $result );

		$result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable .
			" WHERE term_id = 25 AND data_provider = 'f1' AND provider_group_id = '18391'"
		);
		// now check that there is a row with the new attach record
		$this->assertNotNull( $result );
	}

	public function testAttachCTCGroupAlreadyAttachedUpdateException() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$wpdbActual = $wpdb;
		// id of 25 matches the Elders group in the sample data
		$ctcGroup = new CTCI_CTCGroup( 25, 'CTC Group', '' );
		$group = new CTCI_PeopleGroup( 'f1', '37e21', 'My Group', '' );

		// attach the first group
		$response = $this->sut->attachCTCGroup( $ctcGroup, $group );
		$this->assertEquals( 'inserted', $response );

		// attach a new group
		$group = new CTCI_PeopleGroup( 'f1', '18391', 'My Group', '' );
		// set it up to throw exception
		/** @var $wpdb PHPUnit_Framework_MockObject_MockObject */
		$wpdb = $this->getMockBuilder('wpdb')->disableOriginalConstructor()->getMock();
		$wpdb->expects($this->any())
			->method('update')
			->will($this->returnValue(false));
		$this->setExpectedException('CTCI_UpdateCTCGroupAttachRecordException');

		try {
			$this->sut->attachCTCGroup( $ctcGroup, $group );
		} catch (Exception $e) {
			// make sure to reset the global wpdb for other tests
			$wpdb = $wpdbActual;
			throw $e;
		}
		$wpdb = $wpdbActual;
	}

	public function testCreateAttachedCTCGroup() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$group = new CTCI_PeopleGroup('f1', '12345', 'My group', 'My group description');

		$result = $this->sut->createAttachedCTCGroup( $group );

		// check the returned result
		$this->assertInstanceOf( 'CTCI_CTCGroup', $result );
		$this->assertEquals( 'My group', $result->getName() );
		$this->assertEquals( 'My group description', $result->getDescription() );
		// check the term in the db
		$termRecord = get_term( $result->id(), CTCI_WPAL::$ctcPersonGroupTaxonomy, ARRAY_A );
		$this->assertTrue( $termRecord !== false );
		$this->assertFalse( is_wp_error( $termRecord ) );
		$this->assertEquals( $termRecord['name'], 'My group' );
		$this->assertEquals( $termRecord['description'], 'My group description' );
		// check the attach record
		$result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable .
			" WHERE term_id = {$result->id()} AND data_provider = 'f1' AND provider_group_id = '12345'"
		);
		$this->assertNotNull( $result );
	}

	public function testUpdateCTCGroup() {
		$ctcGroup = new CTCI_CTCGroup( 25, 'CTC Group', 'CTC description' );
		$group = new CTCI_PeopleGroup( 'f1', '37e21', 'Provider Group', 'Provider description' );

		// insert the original term
		$ids = wp_insert_term($ctcGroup->getName(), CTCI_WPAL::$ctcPersonGroupTaxonomy, array(
			'description' => $ctcGroup->getDescription())
		);
		$this->assertFalse(is_wp_error($ids));
		// and add the right id for it to our object
		$ctcGroup->setId($ids['term_id']);

		// now update
		$return = $this->sut->updateCTCGroup($ctcGroup, $group);
		$this->assertFalse(is_wp_error($return));
		// and check name and description changed
		$term = get_term($ids['term_id'], CTCI_WPAL::$ctcPersonGroupTaxonomy, ARRAY_A);
		$this->assertNotNull($term, 'Null returned - term empty');
		$this->assertFalse(is_wp_error($term));
		$this->assertEquals($group->getName(), $term['name']);
		$this->assertEquals($group->getDescription(), $term['description']);
	}

	public function testGetCTCGroup() {
		// insert a ctc group / term record
		$ids = wp_insert_term('CTC Group', CTCI_WPAL::$ctcPersonGroupTaxonomy, array(
				'description' => 'CTC Group desc')
		);
		$this->assertFalse(is_wp_error($ids));

		// act
		$ctcGroup = $this->sut->getCTCGroup($ids['term_id']);

		$this->assertInstanceOf('CTCI_CTCGroup', $ctcGroup);
		$this->assertEquals($ids['term_id'], $ctcGroup->id());
		$this->assertEquals('CTC Group', $ctcGroup->getName());
		$this->assertEquals('CTC Group desc', $ctcGroup->getDescription());
	}

	public function testGetCTCGroup_CouldNotRetrieveException() {
		$this->setExpectedException('CTCI_CouldNotRetrieveCTCGroupException');
		// act
		$this->sut->getCTCGroup(192784691);
	}

	public function testGetAttachedCTCGroup() {
		/** @var $wpdb wpdb */
		global $wpdb;
		// insert a ctc group / term record
		$ids = wp_insert_term('CTC Group', CTCI_WPAL::$ctcPersonGroupTaxonomy, array(
				'description' => 'CTC Group desc')
		);
		$this->assertFalse(is_wp_error($ids));
		// insert an attach record for it
		$attachTable = $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable;
		$result = $wpdb->insert( $attachTable, array(
				'data_provider' => 'f1',
				'term_id' => $ids['term_id'],
				'provider_group_id' => '12345'
			), array( '%s', '%d', '%s' )
		);
		$this->assertTrue( $result !== false );
		$group = new CTCI_PeopleGroup('f1', '12345', 'My group', '');

		// act
		$ctcGroup = $this->sut->getAttachedCTCGroup($group);

		$this->assertTrue( is_object( $ctcGroup ) && get_class($ctcGroup) === 'CTCI_CTCGroup' );
		$this->assertEquals( $ctcGroup->id(), $ids['term_id'] );
		$this->assertEquals( $ctcGroup->getName(), 'CTC Group' );
		$this->assertEquals( $ctcGroup->getDescription(), 'CTC Group desc' );
	}

	public function testGetAttachedCTCGroup_NoAttachRecord() {
		$group = new CTCI_PeopleGroup('f1', '12345', 'My group', '');

		// act
		$ctcGroup = $this->sut->getAttachedCTCGroup($group);

		$this->assertNull($ctcGroup);
	}

	public function testGetAttachedCTCGroup_GetTermError() {
		/** @var $wpdb wpdb */
		global $wpdb;
		// insert a ctc group / term record
		$ids = wp_insert_term('CTC Group', CTCI_WPAL::$ctcPersonGroupTaxonomy, array(
				'description' => 'CTC Group desc')
		);
		$this->assertFalse(is_wp_error($ids));
		// insert an attach record for it
		$attachTable = $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable;
		$result = $wpdb->insert( $attachTable, array(
				'data_provider' => 'f1',
				'term_id' => $ids['term_id'],
				'provider_group_id' => '12345'
			), array( '%s', '%d', '%s' )
		);
		$this->assertTrue( $result !== false );
		$group = new CTCI_PeopleGroup('f1', '12345', 'My group', '');

		//change the taxonomy to force the error
		$tax = CTCI_WPAL::$ctcPersonGroupTaxonomy;
		CTCI_WPAL::$ctcPersonGroupTaxonomy = 'rubbish_owehf823938h287fg2io78gf2g7ef827';
		$this->setExpectedException('CTCI_CouldNotRetrieveCTCGroupException');

		// act - resetting the taxonomy string for other tests
		try{
			$this->sut->getAttachedCTCGroup( $group );
		} catch ( Exception $e ) {
			CTCI_WPAL::$ctcPersonGroupTaxonomy = $tax;
			throw $e;
		}
		CTCI_WPAL::$ctcPersonGroupTaxonomy = $tax;
	}

	public function testGetCTCGroupsAttachedViaProvider() {
		$this->insertAttachedCTCGroup( 'Group 1', 'Desc. 1', 'f1', '1111');
		$this->insertAttachedCTCGroup( 'Group 2', 'Desc. 2', 'ccb', '1111');
		$this->insertAttachedCTCGroup( 'Group 3', 'Desc. 3', 'f1', '1112');
		$this->insertAttachedCTCGroup( 'Group 4', 'Desc. 4', 'ccb', '2112');
		$this->insertAttachedCTCGroup( 'Group 5', 'Desc. 5', 'ccb', '2113');
		$this->insertAttachedCTCGroup( 'Group 6', 'Desc. 6', 'f1', '1113');
		
		$f1Groups = $this->sut->getCTCGroupsAttachedViaProvider( 'f1' );
		$ccbGroups = $this->sut->getCTCGroupsAttachedViaProvider( 'ccb' );

		$this->assertTrue( isset( $f1Groups['1111'] ), 'F1:1111 not set' );
		$this->assertTrue( isset( $f1Groups['1112'] ) );
		$this->assertTrue( isset( $f1Groups['1113'] ) );
		$this->assertInstanceOf( 'CTCI_CTCGroupInterface', $f1Groups['1111'] );
		$this->assertInstanceOf( 'CTCI_CTCGroupInterface', $f1Groups['1112'] );
		$this->assertInstanceOf( 'CTCI_CTCGroupInterface', $f1Groups['1113'] );
		$this->assertTrue( is_int( $f1Groups['1111']->id() ) && $f1Groups['1111']->id() > 0);
		$this->assertTrue( is_int( $f1Groups['1112']->id() ) && $f1Groups['1112']->id() > 0);
		$this->assertTrue( is_int( $f1Groups['1113']->id() ) && $f1Groups['1113']->id() > 0);
		$this->assertEquals( 
			'f1:1111:Group 1:Desc. 1',
			implode( ':', array(
				$f1Groups['1111']->getAttachedGroupProviderTag(),
				$f1Groups['1111']->getAttachedGroupProviderId(),
				$f1Groups['1111']->getName(),
				$f1Groups['1111']->getDescription()
			) )
		);
		$this->assertEquals(
			'f1:1112:Group 3:Desc. 3',
			implode( ':', array(
				$f1Groups['1112']->getAttachedGroupProviderTag(),
				$f1Groups['1112']->getAttachedGroupProviderId(),
				$f1Groups['1112']->getName(),
				$f1Groups['1112']->getDescription()
			) )
		);
		$this->assertEquals(
			'f1:1113:Group 6:Desc. 6',
			implode( ':', array(
				$f1Groups['1113']->getAttachedGroupProviderTag(),
				$f1Groups['1113']->getAttachedGroupProviderId(),
				$f1Groups['1113']->getName(),
				$f1Groups['1113']->getDescription()
			) )
		);

		$this->assertTrue( isset( $ccbGroups['1111'] ) );
		$this->assertTrue( isset( $ccbGroups['2112'] ) );
		$this->assertTrue( isset( $ccbGroups['2113'] ) );
		$this->assertInstanceOf( 'CTCI_CTCGroupInterface', $ccbGroups['1111'] );
		$this->assertInstanceOf( 'CTCI_CTCGroupInterface', $ccbGroups['2112'] );
		$this->assertInstanceOf( 'CTCI_CTCGroupInterface', $ccbGroups['2113'] );
		$this->assertTrue( is_int( $ccbGroups['1111']->id() ) && $ccbGroups['1111']->id() > 0);
		$this->assertTrue( is_int( $ccbGroups['2112']->id() ) && $ccbGroups['2112']->id() > 0);
		$this->assertTrue( is_int( $ccbGroups['2113']->id() ) && $ccbGroups['2113']->id() > 0);
		$this->assertEquals(
			'ccb:1111:Group 2:Desc. 2',
			implode( ':', array(
				$ccbGroups['1111']->getAttachedGroupProviderTag(),
				$ccbGroups['1111']->getAttachedGroupProviderId(),
				$ccbGroups['1111']->getName(),
				$ccbGroups['1111']->getDescription()
			) )
		);
		$this->assertEquals(
			'ccb:2112:Group 4:Desc. 4',
			implode( ':', array(
				$ccbGroups['2112']->getAttachedGroupProviderTag(),
				$ccbGroups['2112']->getAttachedGroupProviderId(),
				$ccbGroups['2112']->getName(),
				$ccbGroups['2112']->getDescription()
			) )
		);
		$this->assertEquals(
			'ccb:2113:Group 5:Desc. 5',
			implode( ':', array(
				$ccbGroups['2113']->getAttachedGroupProviderTag(),
				$ccbGroups['2113']->getAttachedGroupProviderId(),
				$ccbGroups['2113']->getName(),
				$ccbGroups['2113']->getDescription()
			) )
		);
	}
	
	protected function insertAttachedCTCGroup( $name, $description, $providerTag, $providerId ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		// insert a ctc group / term record
		$ids = wp_insert_term( $name, CTCI_WPAL::$ctcPersonGroupTaxonomy, array(
				'description' => $description 
			)
		);
		$this->assertFalse( is_wp_error($ids) );
		// insert an attach record for it
		$attachTable = $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable;
		$result = $wpdb->insert( $attachTable, array(
				'data_provider' => $providerTag,
				'term_id' => $ids['term_id'],
				'provider_group_id' => $providerId
			), array( '%s', '%d', '%s' )
		);
		$this->assertTrue( $result !== false );
	}
	
	/*public function testCreateCTCPerson() {

		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson
			->setId(27)
			->setName('New Person')
			->setBio('<h2>Example Heading</h2> This is the bio text, http://www.youtube.com/watch?v=3F1V2fZS7yA')
			->setPosition('Lead Pastor')
			->setPhone('(02) 4211 1111')
			->setEmail('new.person@test.com')
			->setUrlsFromArray(array('http://facebook.com', 'http://twitter.com', 'https://linkedin.com'))
			->setExcerpt('New person excerpt');

		$id = $this->sut->createCTCPerson( $ctcPerson );

		// check id looks ok
		$this->assertNotSame( 0, $id );
		$this->assertFalse( is_wp_error($id) );
		$this->assertTrue( is_int($id) && $id > 0 );
	}*/

	public function testGetCTCPeopleAttachedViaProvider() {

	}
}