<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 13/03/14
 * Time: 3:02 PM
 */

class WP_Test_CTCI_WPALTest extends WP_UnitTestCase {

	/** @var CTCI_WPALInterface */
	protected $sut;

	public function setUp() {
		parent::setUp();
		$this->sut = new CTCI_WPAL();
	}

	public function testAttachCTCGroup() {
		/** @var $wpdb wpdb */
		global $wpdb;
		// id of 25 matches the Elders group in the sample data
		$ctcGroup = new CTCI_CTCGroup( 25, 'CTC Group', '' );
		$group = new CTCI_PeopleGroup( 'f1', '37e21', 'My Group', '' );

		$response = $this->sut->attachCTCGroup( $ctcGroup, $group );

		$this->assertTrue( $response );

		$result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable .
			" WHERE term_id = 25 AND data_provider = 'f1' AND provider_group_id = '37e21'"
		);

		$this->assertNotNull( $result );
	}

	public function testAttachCTCGroupAlreadyAttached() {
		/** @var $wpdb wpdb */
		global $wpdb;
		// id of 25 matches the Elders group in the sample data
		$ctcGroup = new CTCI_CTCGroup( 25, 'CTC Group', '' );
		$group = new CTCI_PeopleGroup( 'f1', '37e21', 'My Group', '' );

		// attach the first group
		$response = $this->sut->attachCTCGroup( $ctcGroup, $group );
		$this->assertTrue( $response );
		// check it's ok
		$result = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . CTCI_WPAL::$ctcGroupConnectTable .
			" WHERE term_id = 25 AND data_provider = 'f1' AND provider_group_id = '37e21'"
		);
		$this->assertNotNull( $result );

		// attach a new group
		$group = new CTCI_PeopleGroup( 'f1', '18391', 'My Group', '' );

		$response = $this->sut->attachCTCGroup( $ctcGroup, $group );
		$this->assertTrue( $response );

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
}
 