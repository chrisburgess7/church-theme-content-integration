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

	public function testPluginActive() {
		$this->assertTrue( is_plugin_active('church-theme-content-integration/church-theme-content-integration.php') );
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

	public function testUnattachCTCGroup() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$attachTable = $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable;

		$this->insertAttachedCTCGroup( 'Test group', 'Test group', 'f1', '9183' );

		// verify it has been recorded
		$ctcGroupConnectRow = $wpdb->get_row(
			"SELECT term_id FROM $attachTable WHERE data_provider = 'f1' AND provider_group_id = '9183'",
			ARRAY_A
		);
		$this->assertNotNull($ctcGroupConnectRow);

		$this->sut->unattachCTCGroup( new CTCI_CTCGroup( $ctcGroupConnectRow['term_id'], 'Test group', 'Test group' ) );

		// verify it has been removed
		$ctcGroupConnectRow = $wpdb->get_row(
			"SELECT term_id FROM $attachTable WHERE data_provider = 'f1' AND provider_group_id = '9183'"
		);
		$this->assertNull($ctcGroupConnectRow);
	}

	public function testDeleteCTCGroup() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$attachTable = $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable;

		$this->insertAttachedCTCGroup( 'Test group', 'Test group', 'f1', '9183' );

		// verify it has been recorded
		$ctcGroupConnectRow = $wpdb->get_row(
			"SELECT term_id FROM $attachTable WHERE data_provider = 'f1' AND provider_group_id = '9183'",
			ARRAY_A
		);
		$termId = $ctcGroupConnectRow['term_id'];
		$this->assertNotNull($ctcGroupConnectRow);
		$ctcGroupRow = get_term( $termId, CTCI_WPAL::$ctcPersonGroupTaxonomy );
		$this->assertNotNull( $ctcGroupRow );
		$this->assertFalse( is_wp_error( $ctcGroupRow ) );

		$this->sut->deleteCTCGroup( new CTCI_CTCGroup( $termId, 'Test group', 'Test group' ) );

		// verify it has been removed
		$ctcGroupConnectRow = $wpdb->get_row(
			"SELECT term_id FROM $attachTable WHERE data_provider = 'f1' AND provider_group_id = '9183'"
		);
		$this->assertNull($ctcGroupConnectRow);
		$ctcGroupRow = get_term( $termId, CTCI_WPAL::$ctcPersonGroupTaxonomy );
		$this->assertNull( $ctcGroupRow );
	}

	public function testGetUnattachedCTCGroups() {
		$unattachedGroups = array();

		// insert some groups
		$ids = wp_insert_term( 'Group 1', CTCI_WPAL::$ctcPersonGroupTaxonomy, array( 'description' => 'G1d' ) );
		$unattachedGroups[ $ids['term_id'] ] = new CTCI_CTCGroup( $ids['term_id'], 'Group 1', 'G1d' );
		$this->insertAttachedCTCGroup( 'Group 2', 'G2d', 'f1' , '1234' );
		$ids = wp_insert_term( 'Group 3', CTCI_WPAL::$ctcPersonGroupTaxonomy, array( 'description' => 'G3d' ) );
		$unattachedGroups[ $ids['term_id'] ] = new CTCI_CTCGroup( $ids['term_id'], 'Group 3', 'G3d' );
		$this->insertAttachedCTCGroup( 'Group 4', 'G4d', 'ccb' , '9876' );

		$actual = $this->sut->getUnattachedCTCGroups();

		$this->assertEquals( $unattachedGroups, $actual );
	}

	public function testCreateAttachedCTCPerson() {
		$personId = 270;
		$person = new CTCI_Person( 'f1', $personId );
		$person
			->setFirstName('New')
			->setLastName('Person')
			->setPosition('Lead Pastor')
			->setPhone('(02) 4211 1111')
			->setEmail('new.person@test.com')
			->setFacebookURL( 'http://facebook.com/newuser' )
			->setTwitterURL( 'http://twitter.com/newuser')
			->setLinkedInURL( 'https://linkedin.com/newuser' );

		$person
			->setSyncName()
			->setSyncPosition()
			->setSyncPhone()
			->setSyncEmail()
			->setSyncFacebookURL()
			->setSyncTwitterURL()
			->setSyncLinkedInURL();

		$ctcPerson = $this->sut->createAttachedCTCPerson( $person );

		// check id looks ok
		$this->assertInstanceOf( 'CTCI_CTCPersonInterface', $ctcPerson );
		$id = $ctcPerson->id();
		$post = get_post( $id );
		$this->assertInstanceOf( 'WP_Post', $post );
		$this->assertEquals( CTCI_WPAL::$ctcPersonPostType, $post->post_type );
		$this->assertEquals( 'New Person', $post->post_title );
		$this->assertEquals( 'New Person', $ctcPerson->getName() );
		$this->assertEquals( 'Lead Pastor', get_post_meta( $id, CTCI_WPAL::$ctcPersonPositionMetaTag, true ) );
		$this->assertEquals( 'Lead Pastor', $ctcPerson->getPosition() );
		$this->assertEquals( '(02) 4211 1111', get_post_meta( $id, CTCI_WPAL::$ctcPersonPhoneMetaTag, true ) );
		$this->assertEquals( '(02) 4211 1111', $ctcPerson->getPhone() );
		$this->assertEquals( 'new.person@test.com', get_post_meta( $id, CTCI_WPAL::$ctcPersonEmailMetaTag, true ) );
		$this->assertEquals( 'new.person@test.com', $ctcPerson->getEmail() );
		$this->assertEquals(
			"http://facebook.com/newuser\nhttp://twitter.com/newuser\nhttps://linkedin.com/newuser",
			get_post_meta( $id, CTCI_WPAL::$ctcPersonURLSMetaTag, true )
		);
		$this->assertEquals(
			"http://facebook.com/newuser\nhttp://twitter.com/newuser\nhttps://linkedin.com/newuser",
			$ctcPerson->getURLs()
		);

		// check attach record
		$this->assertEquals( 'f1', get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag, true ) );
		$this->assertEquals( $personId, get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag, true ) );

	}

	public function testUpdateCTCPerson() {
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id) && $id > 0 );
		// create object to match DB
		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$ctcPerson->setName('Test Person');
		// now edit it
		$ctcPerson->editName('Edited Person');
		$ctcPerson->editBio('Edited bio');
		$ctcPerson->editPosition('Edited position');
		$ctcPerson->editEmail('edit@test.com');
		$ctcPerson->editPhone('4299 9999');
		$ctcPerson->editURL('http://www.facebook.com/editedusername');
		$ctcPerson->editURL('http://twitter.com/editedusername');
		$ctcPerson->editExcerpt('Edited excerpt');

		$success = $this->sut->updateCTCPerson( $ctcPerson );

		$this->assertTrue( $success );
		$post = get_post( $id );
		$this->assertEquals( 'Edited Person', $post->post_title );
		$this->assertEquals( 'Edited bio', $post->post_content );
		$this->assertEquals( 'Edited position', get_post_meta( $id, CTCI_WPAL::$ctcPersonPositionMetaTag, true ) );
		$this->assertEquals( 'edit@test.com', get_post_meta( $id, CTCI_WPAL::$ctcPersonEmailMetaTag, true ) );
		$this->assertEquals( '4299 9999', get_post_meta( $id, CTCI_WPAL::$ctcPersonPhoneMetaTag, true ) );
		$this->assertEquals(
			"http://www.facebook.com/editedusername\nhttp://twitter.com/editedusername",
			get_post_meta( $id, CTCI_WPAL::$ctcPersonURLSMetaTag, true )
		);
		$this->assertEquals( 'Edited excerpt', $post->post_excerpt );
	}

	public function testUpdateCTCPerson_PartialUpdateOnly() {
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
			'post_content' => 'An unchanged bio',
			'post_excerpt' => 'An unchanged excerpt'
		));
		$this->assertTrue( is_int($id) && $id > 0 );
		$this->assertTrue( false !== update_post_meta( $id, CTCI_WPAL::$ctcPersonPositionMetaTag, 'Unchanged Position') );
		$this->assertTrue(
			false !== update_post_meta(
				$id, CTCI_WPAL::$ctcPersonURLSMetaTag, "http://www.facebook.com/username\nhttp://twitter.com/username"
			)
		);
		// set object to match DB
		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$ctcPerson->setName('Test Person');
		$ctcPerson->setBio('An unchanged bio');
		$ctcPerson->setExcerpt('An unchanged excerpt');
		$ctcPerson->setPosition('Unchanged Position');
		$ctcPerson->setUrls("http://www.facebook.com/username\nhttp://twitter.com/username");
		// now perform edits
		$ctcPerson->editName('Edited Person');
		$ctcPerson->editEmail('edit@test.com');
		$ctcPerson->editPhone('4299 9999');
		$ctcPerson->editURL('http://twitter.com/editedusername');

		$success = $this->sut->updateCTCPerson( $ctcPerson );

		$this->assertTrue( $success );
		$post = get_post( $id );
		$this->assertEquals( 'Edited Person', $post->post_title );
		$this->assertEquals( 'An unchanged bio', $post->post_content );
		$this->assertEquals( 'Unchanged Position', get_post_meta( $id, CTCI_WPAL::$ctcPersonPositionMetaTag, true ) );
		$this->assertEquals( 'edit@test.com', get_post_meta( $id, CTCI_WPAL::$ctcPersonEmailMetaTag, true ) );
		$this->assertEquals( '4299 9999', get_post_meta( $id, CTCI_WPAL::$ctcPersonPhoneMetaTag, true ) );
		$this->assertEquals(
			"http://www.facebook.com/username\nhttp://twitter.com/editedusername",
			get_post_meta( $id, CTCI_WPAL::$ctcPersonURLSMetaTag, true )
		);
		$this->assertEquals( 'An unchanged excerpt', $post->post_excerpt );
	}

	public function testAttachCTCPerson() {
		// create a person post
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
		));
		$this->assertTrue( is_int($id) && $id > 0 );
		// check nothing attached
		$this->assertEmpty( get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag, true ) );
		$this->assertEmpty( get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag, true ) );
		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$person = new CTCI_Person('f1', '8a781');

		// first attach with mode new, should be fine
		$success = $this->sut->attachCTCPerson( $ctcPerson, $person, 'new' );

		$this->assertTrue( $success );
		$this->assertEquals( array( 'f1' ), get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag ) );
		$this->assertEquals( array( '8a781' ), get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag ) );

		$person->setId( '12345' );
		$person->setProviderTag( 'ccb' );

		// now we have a record, calling again with mode new should simply return false with no change
		$success = $this->sut->attachCTCPerson( $ctcPerson, $person, 'new' );
		$this->assertFalse( $success );
		// having the array here ensures we test that we dont have multiple entries
		$this->assertEquals( array( 'f1' ), get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag ) );
		$this->assertEquals( array( '8a781' ), get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag ) );

		// now call with mode replace, which should update the record with the new id, even with an existing attachment
		$success = $this->sut->attachCTCPerson( $ctcPerson, $person, 'replace' );
		$this->assertTrue( $success );
		$this->assertEquals( array( 'ccb' ), get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag ) );
		$this->assertEquals( array( '12345' ), get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag ) );

		// and repeating in mode replace should still return true, even with the same values
		$success = $this->sut->attachCTCPerson( $ctcPerson, $person, 'replace' );
		$this->assertTrue( $success );
	}

	public function testUnattachCTCPerson() {
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id) && $id > 0 );
		update_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'f1' );
		update_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag, '9e73' );
		// quick check that post meta added correctly
		$this->assertEquals( 'f1', get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag, true ) );
		$this->assertEquals( '9e73', get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag, true ) );

		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$return = $this->sut->unattachCTCPerson( $ctcPerson );

		$this->assertTrue( $return );
		$this->assertEquals( '', get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag, true ) );
		$this->assertEquals( '', get_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag, true ) );
	}

	public function testSetCTCPersonsGroups() {
		// setup db
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
		));
		$this->assertTrue( is_int($id) && $id > 0 );
		$ids = wp_insert_term( 'Group 1', CTCI_WPAL::$ctcPersonGroupTaxonomy );
		$this->assertTrue( isset( $ids['term_id'] ) );
		$this->assertEmpty( wp_get_object_terms( $id, CTCI_WPAL::$ctcPersonGroupTaxonomy ) );
		/** @var $wpdb wpdb */
		global $wpdb;
		// insert an attach record for it
		$attachTable = $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable;
		$result = $wpdb->insert( $attachTable, array(
				'data_provider' => 'f1',
				'term_id' => $ids['term_id'],
				'provider_group_id' => '12345'
			), array( '%s', '%d', '%s' )
		);
		$this->assertTrue( $result !== false );
		// create objects with same data
		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id )->setName( 'Test Person' );
		$group = new CTCI_PeopleGroup( 'f1', '12345', 'Group 1', '');

		// act
		$this->sut->setCTCPersonsGroups( $ctcPerson, array( $group ) );

		$objectTerms = wp_get_object_terms( $ctcPerson->id(), CTCI_WPAL::$ctcPersonGroupTaxonomy );
		$this->assertCount( 1, $objectTerms );
		$this->assertEquals( $ids['term_id'], $objectTerms[0]->term_id );
	}

	public function testDeleteCTCPerson() {
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertNotNull( get_post( $id ) );

		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$this->sut->deleteCTCPerson( $ctcPerson );

		$this->assertNull( get_post( $id ) );
	}

	public function testUnpublishCTCPerson() {
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
			'post_status' => 'publish'
		));
		$this->assertEquals( 'publish', get_post( $id )->post_status );

		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$this->sut->unpublishCTCPerson( $ctcPerson );

		$this->assertEquals( 'draft', get_post( $id )->post_status );
	}

	public function testPublishCTCPerson() {
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
			'post_status' => 'draft'
		));
		$this->assertEquals( 'draft', get_post( $id )->post_status );

		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$this->sut->publishCTCPerson( $ctcPerson );

		$this->assertEquals( 'publish', get_post( $id )->post_status );
	}

	public function testGetCTCPeopleAttachedViaProvider() {
		// create a person post
		$id[0] = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
			'post_content' => 'Test person bio',
			'post_excerpt' => 'Test person excerpt'
		));
		$this->assertTrue( is_int($id[0]) && $id[0] > 0);
		// add an attach record
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'f1' );
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonProviderIdMetaTag, '9e73' );
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonPositionMetaTag, 'Leader' );
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonPhoneMetaTag, '4200 0000' );
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonEmailMetaTag, 'test@gmail.com' );
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonURLSMetaTag, "http://facebook.com\nhttp://twitter.com" );
		// person 2 not attached
		$id[1] = wp_insert_post( array(
			'post_title' => 'Test Person 2',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id[1]) && $id[1] > 0);
		// person 3 attached to another provider
		$id[2] = wp_insert_post( array(
			'post_title' => 'Test Person 3',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id[2]) && $id[2] > 0);
		// add an attach record
		update_post_meta( $id[2], CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'ccb' );
		update_post_meta( $id[2], CTCI_WPAL::$ctcPersonProviderIdMetaTag, '1782' );
		// person 4 attached
		$id[3] = wp_insert_post( array(
			'post_title' => 'Test Person 4',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id[3]) && $id[3] > 0);
		// add an attach record
		update_post_meta( $id[3], CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'f1' );
		update_post_meta( $id[3], CTCI_WPAL::$ctcPersonProviderIdMetaTag, 'ab81' );

		$f1People = $this->sut->getCTCPeopleAttachedViaProvider( 'f1' );
		$ccbPeople = $this->sut->getCTCPeopleAttachedViaProvider( 'ccb' );

		// assert f1
		$this->assertTrue( is_array( $f1People ) );
		$this->assertEquals( 2, count( $f1People ) );
		foreach ( $f1People as $ctcPerson ) {
			$this->assertInstanceOf( 'CTCI_CTCPerson', $ctcPerson );
		}
		$this->assertTrue( isset( $f1People[ $id[0] ] ) );
		$this->assertEquals( $f1People[ $id[0] ]->id(), $id[0] );
		$this->assertEquals( $f1People[ $id[0] ]->getName(), 'Test Person' );
		$this->assertEquals( 'Test person bio', $f1People[ $id[0] ]->getBio() );
		$this->assertEquals( 'Test person excerpt', $f1People[ $id[0] ]->getExcerpt() );
		$this->assertEquals( 'Leader', $f1People[ $id[0] ]->getPosition() );
		$this->assertEquals( '4200 0000', $f1People[ $id[0] ]->getPhone() );
		$this->assertEquals( 'test@gmail.com', $f1People[ $id[0] ]->getEmail() );
		$this->assertEquals( "http://facebook.com\nhttp://twitter.com", $f1People[ $id[0] ]->getURLs() );
		$this->assertTrue( isset( $f1People[ $id[3] ] ) );
		$this->assertEquals( $f1People[ $id[3] ]->id(), $id[3] );
		$this->assertEquals( $f1People[ $id[3] ]->getName(), 'Test Person 4' );

		// assert ccb
		$this->assertTrue( is_array( $ccbPeople ) && count( $ccbPeople ) === 1 );
		foreach ( $ccbPeople as $ctcPerson ) {
			$this->assertInstanceOf( 'CTCI_CTCPerson', $ctcPerson );
		}
		$this->assertTrue( isset( $ccbPeople[ $id[2] ] ) );
		$this->assertEquals( $ccbPeople[ $id[2] ]->id(), $id[2] );
		$this->assertEquals( $ccbPeople[ $id[2] ]->getName(), 'Test Person 3' );

	}

	public function testGetCTCPeopleAttachedViaProvider_MoreThan5People() {
		// the get_posts method by default only returns 5 posts
		// we'll test that we are getting all of them here

		$posts = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$posts[$i] = wp_insert_post( array(
				'post_title' => 'Test Person ' . $i,
				'post_type' => CTCI_WPAL::$ctcPersonPostType,
			));
			// quick check that the create post looks ok
			$this->assertTrue( is_int($posts[$i]) && $posts[$i] > 0);
			// add an attach record
			update_post_meta( $posts[$i], CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'f1' );
			update_post_meta( $posts[$i], CTCI_WPAL::$ctcPersonProviderIdMetaTag, '1234' . $i );
		}

		$people = $this->sut->getCTCPeopleAttachedViaProvider('f1');

		$this->assertCount( 10, $people );

	}

	public function testGetAttachedPersonId() {
		$id = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id) && $id > 0);
		update_post_meta( $id, CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'f1' );
		update_post_meta( $id, CTCI_WPAL::$ctcPersonProviderIdMetaTag, '9e73' );

		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson->setId( $id );
		$attachedId = $this->sut->getAttachedPersonId( $ctcPerson );

		$this->assertEquals( '9e73', $attachedId );
	}

	public function testGetUnattachedCTCPeople() {
		// create a person post
		$id[0] = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
		));
		$this->assertTrue( is_int($id[0]) && $id[0] > 0);
		// add an attach record
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'f1' );
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonProviderIdMetaTag, '9e73' );
		// person 2 not attached
		$id[1] = wp_insert_post( array(
			'post_title' => 'Test Person 2',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
			'post_content' => 'Test person bio',
			'post_excerpt' => 'Test person excerpt',
		));
		$this->assertTrue( is_int($id[1]) && $id[1] > 0);
		update_post_meta( $id[1], CTCI_WPAL::$ctcPersonPositionMetaTag, 'Leader' );
		update_post_meta( $id[1], CTCI_WPAL::$ctcPersonPhoneMetaTag, '4200 0000' );
		update_post_meta( $id[1], CTCI_WPAL::$ctcPersonEmailMetaTag, 'test@gmail.com' );
		update_post_meta( $id[1], CTCI_WPAL::$ctcPersonURLSMetaTag, "http://facebook.com\nhttp://twitter.com" );
		// person 3 attached to another provider
		$id[2] = wp_insert_post( array(
			'post_title' => 'Test Person 3',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id[2]) && $id[2] > 0);
		// add an attach record
		update_post_meta( $id[2], CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'ccb' );
		update_post_meta( $id[2], CTCI_WPAL::$ctcPersonProviderIdMetaTag, '1782' );
		// person 4 not attached
		$id[3] = wp_insert_post( array(
			'post_title' => 'Test Person 4',
			'post_type' => CTCI_WPAL::$ctcPersonPostType
		));
		$this->assertTrue( is_int($id[3]) && $id[3] > 0);

		$ctcPeople = $this->sut->getUnattachedCTCPeople();

		$this->assertTrue( is_array( $ctcPeople ) );
		$this->assertEquals( 2, count( $ctcPeople ) );
		foreach ( $ctcPeople as $ctcPerson ) {
			$this->assertInstanceOf( 'CTCI_CTCPerson', $ctcPerson );
		}
		$this->assertTrue( isset( $ctcPeople[ $id[1] ] ) );
		$this->assertEquals( $ctcPeople[ $id[1] ]->id(), $id[1] );
		$this->assertEquals( $ctcPeople[ $id[1] ]->getName(), 'Test Person 2' );
		$this->assertEquals( 'Test person bio', $ctcPeople[ $id[1] ]->getBio() );
		$this->assertEquals( 'Test person excerpt', $ctcPeople[ $id[1] ]->getExcerpt() );
		$this->assertEquals( 'Leader', $ctcPeople[ $id[1] ]->getPosition() );
		$this->assertEquals( '4200 0000', $ctcPeople[ $id[1] ]->getPhone() );
		$this->assertEquals( 'test@gmail.com', $ctcPeople[ $id[1] ]->getEmail() );
		$this->assertEquals( "http://facebook.com\nhttp://twitter.com", $ctcPeople[ $id[1] ]->getURLs() );
		$this->assertTrue( isset( $ctcPeople[ $id[3] ] ) );
		$this->assertEquals( $ctcPeople[ $id[3] ]->id(), $id[3] );
		$this->assertEquals( $ctcPeople[ $id[3] ]->getName(), 'Test Person 4' );
	}

	public function testGetUnattachedCTCPeople_MoreThan5() {
		// create an attached person
		$id[0] = wp_insert_post( array(
			'post_title' => 'Test Person',
			'post_type' => CTCI_WPAL::$ctcPersonPostType,
		));
		$this->assertTrue( is_int($id[0]) && $id[0] > 0);
		// add an attach record
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonProviderTagMetaTag, 'f1' );
		update_post_meta( $id[0], CTCI_WPAL::$ctcPersonProviderIdMetaTag, '9e73' );

		// and a bunhc of unattached
		for ( $i = 1; $i <= 6; $i++ ) {
			$id[ $i ] = wp_insert_post( array(
				'post_title' => "Test Person $i",
				'post_type' => CTCI_WPAL::$ctcPersonPostType
			));
			$this->assertTrue( is_int($id[$i]) && $id[$i] > 0);
		}

		$ctcPeople = $this->sut->getUnattachedCTCPeople();

		$this->assertTrue( is_array( $ctcPeople ) );
		$this->assertEquals( 6, count( $ctcPeople ) );
		foreach ( $ctcPeople as $ctcPerson ) {
			$this->assertInstanceOf( 'CTCI_CTCPerson', $ctcPerson );
		}
		for ( $i = 1; $i <= 6; $i++ ) {
			$this->assertTrue( isset( $ctcPeople[ $id[ $i ] ] ) );
		}
	}

	public function testClearSyncStatus() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query(
			"REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages)
			VALUES (1, 'A test message', 1, 'An error message', 2, 'A warning message')" );

		$this->assertNotEquals( array(
				'id' => 1, 'message' => null, 'errors' => 0, 'error_messages' => null, 'warnings' => 0, 'warning_messages' => null
			), $wpdb->get_row("SELECT * FROM $syncTable WHERE id = 1", ARRAY_A)
		);

		$this->sut->clearSyncStatus();

		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
		$this->assertEquals( array(
				'id' => 1, 'message' => null, 'errors' => 0, 'error_messages' => null, 'warnings' => 0, 'warning_messages' => null
			), $wpdb->get_row("SELECT * FROM $syncTable WHERE id = 1", ARRAY_A)
		);
	}

	public function testSetSyncMessage() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );
		$message = 'A test message';

		$this->sut->setSyncMessage( $message );

		$this->assertEquals( $message, $wpdb->get_var( "SELECT message FROM $syncTable WHERE id = 1" ) );

		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}

	public function testSetSyncMessage_Replace() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->update( $syncTable, array( 'message' => 'Replaced message' ), array( 'id' => 1 ) );
		$message = 'A test message';

		$this->sut->setSyncMessage( $message );

		$this->assertEquals( $message, $wpdb->get_var( "SELECT message FROM $syncTable" ) );

		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}
	
	public function testAddSyncError_NoMessage() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );

		$this->sut->addSyncError();
		
		$this->assertEquals( 1, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );
		
		$this->sut->addSyncError();

		$this->assertEquals( 2, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );

		$this->sut->addSyncError();

		$this->assertEquals( 3, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );

		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}

	public function testAddSyncError_Messages() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );

		$this->sut->addSyncError('Message 1');

		$this->assertEquals( 1, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );
		$this->assertEquals( 'Message 1', $wpdb->get_var( "SELECT error_messages FROM $syncTable" ) );

		$this->sut->addSyncError('Message 2');

		$this->assertEquals( 2, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );
		$this->assertEquals( 'Message 2', $wpdb->get_var( "SELECT error_messages FROM $syncTable" ) );

		$this->sut->addSyncError('Message 3');

		$this->assertEquals( 3, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );
		$this->assertEquals( 'Message 3', $wpdb->get_var( "SELECT error_messages FROM $syncTable" ) );

		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}

	public function testAddSyncError_MessagesAndNoMessage() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );

		$this->sut->addSyncError('Message 1');

		$this->assertEquals( 1, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );
		$this->assertEquals( 'Message 1', $wpdb->get_var( "SELECT error_messages FROM $syncTable" ) );

		$this->sut->addSyncError();

		$this->assertEquals( 2, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );
		$this->assertEquals( 'Message 1', $wpdb->get_var( "SELECT error_messages FROM $syncTable" ) );

		$this->sut->addSyncError('Message 3');

		$this->assertEquals( 3, $wpdb->get_var( "SELECT errors FROM $syncTable" ) );
		$this->assertEquals( 'Message 3', $wpdb->get_var( "SELECT error_messages FROM $syncTable" ) );

		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}

	public function testAddSyncWarning_NoMessage() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );

		$this->sut->addSyncWarning();

		$this->assertEquals( 1, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );

		$this->sut->addSyncWarning();

		$this->assertEquals( 2, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );

		$this->sut->addSyncWarning();

		$this->assertEquals( 3, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );

		$this->assertEquals(1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}

	public function testAddSyncWarning_Messages() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );

		$this->sut->addSyncWarning('Message 1');

		$this->assertEquals( 1, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );
		$this->assertEquals( 'Message 1', $wpdb->get_var( "SELECT warning_messages FROM $syncTable" ) );

		$this->sut->addSyncWarning('Message 2');

		$this->assertEquals( 2, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );
		$this->assertEquals( 'Message 2', $wpdb->get_var( "SELECT warning_messages FROM $syncTable" ) );

		$this->sut->addSyncWarning('Message 3');

		$this->assertEquals( 3, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );
		$this->assertEquals( 'Message 3', $wpdb->get_var( "SELECT warning_messages FROM $syncTable" ) );

		$this->assertEquals(1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}

	public function testAddSyncWarning_MessagesAndNoMessage() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );

		$this->sut->addSyncWarning('Message 1');

		$this->assertEquals( 1, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );
		$this->assertEquals( 'Message 1', $wpdb->get_var( "SELECT warning_messages FROM $syncTable" ) );

		$this->sut->addSyncWarning();

		$this->assertEquals( 2, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );
		$this->assertEquals( 'Message 1', $wpdb->get_var( "SELECT warning_messages FROM $syncTable" ) );

		$this->sut->addSyncWarning('Message 3');

		$this->assertEquals( 3, $wpdb->get_var( "SELECT warnings FROM $syncTable" ) );
		$this->assertEquals( 'Message 3', $wpdb->get_var( "SELECT warning_messages FROM $syncTable" ) );

		$this->assertEquals(1, $wpdb->get_var( "SELECT COUNT(*) FROM $syncTable" ) );
	}

	public function testGetSyncStatusAsJSON() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$syncTable = $wpdb->prefix . CTCI_WPAL::$syncStatusTable;
		$wpdb->query( "REPLACE INTO $syncTable (id, message, errors, error_messages, warnings, warning_messages) VALUES (1, NULL, 0, NULL, 0, NULL)" );
		$contents = array(
			'message' => 'A test message',
			'errors' => 0,
			'error_messages' => null,
			'warnings' => 1,
			'warning_messages' => 'A warning'
		);
		$wpdb->update( $syncTable, $contents, array( 'id' => 1 ) );

		$actualJSON = $this->sut->getSyncStatusAsJSON();

		// convert some values to json format
		$contents['errors'] = "0";
		$contents['error_messages'] = "";
		$contents['warnings'] = "1";
		$this->assertEquals( json_encode( $contents ), $actualJSON );

	}
}