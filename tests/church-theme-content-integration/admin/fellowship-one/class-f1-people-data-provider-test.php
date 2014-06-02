<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 24/03/14
 * Time: 12:19 PM
 */

require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/fellowship-one/class-f1-people-data-provider.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/fellowship-one/fellowship-one.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/fellowship-one/OAuth/class-f1-oauth-client.php';
// required so that phpunit recognises type hinting
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/class-logger.php';
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/interface-operation.php';

class CTCI_F1PeopleDataProviderTest extends \PHPUnit_Framework_TestCase {

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $authClientMock;
	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $peopleSyncSettingsMock;
	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $loggerMock;

	/**
	 * @var CTCI_F1PeopleDataProvider
	 */
	protected $sutF1PeopleDataProvider;

	public function setUp() {
		$this->authClientMock = $this->getMockBuilder('CTCI_F1OAuthClient')
			->disableOriginalConstructor()
			->getMock();

		$this->authClientMock
			->expects($this->any())
			->method('json')
			->will($this->returnSelf());

		$this->peopleSyncSettingsMock = $this->getMockBuilder('CTCI_Fellowship_One')
			->disableOriginalConstructor()
			->getMock();

		$this->loggerMock = $this->getMockBuilder('CTCI_Logger')
			->disableOriginalConstructor()
			->getMock();

		$this->sutF1PeopleDataProvider = new CTCI_F1PeopleDataProvider(
			$this->authClientMock, $this->peopleSyncSettingsMock, $this->loggerMock
		);
	}

	public function testSetupForPeopleSync_1() {
		/*
		 * Sync groups true
		 * 1 people list to sync, called Staff, with 3 people in it
		 * All sync options set to true
		 */
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPeopleGroups')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('getF1PeopleLists')
			->will($this->returnValue(array('Staff')));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPosition')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1PersonPositionAttribute')
			->will($this->returnValue('Church Position'));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPhone')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonEmail')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonFacebookURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonTwitterURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonLinkedInURL')
			->will($this->returnValue(true));

		// build a list of 2 groups
		$groupsJSON = $this->buildPeopleListsJSON( array(
			array(
				'id' => '1234',
				'name' => 'Group Leaders',
			),
			array(
				'id' => '1235',
				'name' => 'Staff',
				'description' => 'All full-time staff'
			)
		));
		// check that the test json data is well formed
		$this->assertNotNull( json_decode( $groupsJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleLists')
			->will($this->returnValue( $groupsJSON ));

		// these are the members of the Staff people list
		$memberListJSON = $this->buildMemberListJSON( array(
			'id' => '1235', 'name' => 'Staff'
		), array(
			array(
				'id' => '12345',
				'person-id' => '123456',
				'name' => 'Steve Jobs'
			), array(
				'id' => '12346',
				'person-id' => '123457',
				'name' => 'Max Once'
			), array(
				'id' => '12347',
				'person-id' => '123458',
				'name' => 'John Doe'
			)
		));
		$this->assertNotNull( json_decode( $memberListJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleListMembers')
			->will($this->returnValue($memberListJSON));

		// details of each member of the Staff group
		// the third person covers title, firstname, lastname, middlename, goesbyname and suffix
		$person1JSON = $this->buildPersonJSON( array(
			'id' => '123456',
			'title' => 'Dr',
			'firstName' => 'Steve',
			'lastName' => 'Jobs'
		));
		$this->assertNotNull( json_decode( $person1JSON ) );
		$person2JSON = $this->buildPersonJSON( array(
			'id' => '123457',
			'firstName' => 'Max',
			'lastName' => 'Once'
		));
		$this->assertNotNull( json_decode( $person2JSON ) );
		$person3JSON = $this->buildPersonJSON( array(
			'id' => '123458',
			'title' => 'Mr',
			'firstName' => 'Johnathan',
			'lastName' => 'Doe',
			'middleName' => 'Edward',
			'goesByName' => 'John',
			'suffix' => 'II'
		));
		$this->assertNotNull( json_decode( $person3JSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPerson')
			->will($this->returnValueMap(array(
				array( 123456, $person1JSON ), array( 123457, $person2JSON ), array( 123458, $person3JSON ),
				array( '123456', $person1JSON ), array( '123457', $person2JSON ), array( '123458', $person3JSON )
			)));

		// add communications data for each person
		// person 1: home phone, email
		// person 2: home, work, mobile (preferred) phone. email (preferred), home email, work email, plus facebook
		// person 3: home, work (preferred), mobile phone. email, home email (preferred), work email, plus facebook, twitter and linkedin
		$person1CommJSON = $this->buildCommunicationsJSON( '123456', array(
			array(
				'id' => '1111',
				'type' => 'Home Phone',
				'value' => '4211 1111',
				'preferred' => true
			),
			array(
				'id' => '1112',
				'type' => 'Email',
				'value' => 'person1@test.com',
				'preferred' => true
			)
		));
		$this->assertNotNull( json_decode( $person1CommJSON ) );
		$person2CommJSON = $this->buildCommunicationsJSON( '123457', array(
			array(
				'id' => '1111',
				'type' => 'Home Phone',
				'value' => '4211 1112',
				'preferred' => false
			),
			array(
				'id' => '1113',
				'type' => 'Work Phone',
				'value' => '4299 1112',
				'preferred' => false
			),
			array(
				'id' => '1112',
				'type' => 'Mobile Phone',
				'value' => '0400 111 112',
				'preferred' => true
			),
			array(
				'id' => '1114',
				'type' => 'Email',
				'value' => 'person2@test.com',
				'preferred' => true
			),
			array(
				'id' => '1115',
				'type' => 'Home Email',
				'value' => 'person2_home@test.com',
				'preferred' => false
			),
			array(
				'id' => '1116',
				'type' => 'Work Email',
				'value' => 'person2_work@test.com',
				'preferred' => false
			),
			array(
				'id' => '1120',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person2',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person2CommJSON ) );
		$person3CommJSON = $this->buildCommunicationsJSON( '123458', array(
			array(
				'id' => '1111',
				'type' => 'Home Phone',
				'value' => '4211 1113',
				'preferred' => false
			),
			array(
				'id' => '1113',
				'type' => 'Work Phone',
				'value' => '4299 1113',
				'preferred' => true
			),
			array(
				'id' => '1112',
				'type' => 'Mobile Phone',
				'value' => '0400 111 113',
				'preferred' => false
			),
			array(
				'id' => '1114',
				'type' => 'Email',
				'value' => 'person3@test.com',
				'preferred' => false
			),
			array(
				'id' => '1115',
				'type' => 'Home Email',
				'value' => 'person3_home@test.com',
				'preferred' => true
			),
			array(
				'id' => '1116',
				'type' => 'Work Email',
				'value' => 'person3_work@test.com',
				'preferred' => false
			),
			array(
				'id' => '1120',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person3',
				'preferred' => false
			),
			array(
				'id' => '1121',
				'type' => 'Linked-In',
				'value' => 'http://www.linkedin.com/in/person3',
				'preferred' => false
			),
			array(
				'id' => '1122',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person3',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person3CommJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonCommunications')
			->will($this->returnValueMap(array(
				array( 123456, $person1CommJSON ), array( 123457, $person2CommJSON ), array( 123458, $person3CommJSON ),
				array( '123456', $person1CommJSON ), array( '123457', $person2CommJSON ), array( '123458', $person3CommJSON )
			)));

		// add attributes for position, with attribute name being 'Church Position'
		$person1AttrJSON = $this->buildPersonAttributesJSON( '123456', array(
			array(
				'id' => '111111',
				'group-id' => '111',
				'group-attr-id' => '1111',
				'group-name' => 'Not Church Position',
				'attr-name' => 'something completely other'
			), array(
				'id' => '111112',
				'group-id' => '112',
				'group-attr-id' => '1112',
				'group-name' => 'Not Church Position',
				'attr-name' => 'another other'
			), array(
				'id' => '111113',
				'group-id' => '113',
				'group-attr-id' => '1113',
				'group-name' => 'Church Position',
				'attr-name' => 'IT Manager'
			), array(
				'id' => '111114',
				'group-id' => '114',
				'group-attr-id' => '1114',
				'group-name' => 'Abilities',
				'attr-name' => 'Presentation'
			)
		));
		$this->assertNotNull( json_decode( $person1AttrJSON ) );
		$person2AttrJSON = $this->buildPersonAttributesJSON( '123457', array(
			array(
				'id' => '211111',
				'group-id' => '211',
				'group-attr-id' => '2111',
				'group-name' => 'Church Position',
				'attr-name' => 'Senior Minister'
			)
		));
		$this->assertNotNull( json_decode( $person2AttrJSON ) );
		$person3AttrJSON = $this->buildPersonAttributesJSON( '123458', array(
			array(
				'id' => '311101',
				'group-id' => '301',
				'group-attr-id' => '3101',
				'group-name' => 'Gifts',
				'attr-name' => 'Public Speaking'
			), array(
				'id' => '311102',
				'group-id' => '302',
				'group-attr-id' => '3102',
				'group-name' => 'Gifts',
				'attr-name' => 'Web Design'
			), array(
				'id' => '311111',
				'group-id' => '311',
				'group-attr-id' => '3111',
				'group-name' => 'Church Position',
				'attr-name' => 'Youth Minister'
			), array(
				'id' => '311112',
				'group-id' => '312',
				'group-attr-id' => '3112',
				'group-name' => 'Church Position',
				'attr-name' => 'Social Media Manager'
			), array(
				'id' => '311121',
				'group-id' => '321',
				'group-attr-id' => '3121',
				'group-name' => 'Training X Status',
				'attr-name' => 'Completed'
			), array(
				'id' => '311131',
				'group-id' => '331',
				'group-attr-id' => '3131',
				'group-name' => 'Favourite Sport',
				'attr-name' => 'Football'
			)
		));
		$this->authClientMock
			->expects($this->any())
			->method('getPersonAttributes')
			->will($this->returnValueMap(array(
				array( 123456, $person1AttrJSON ), array( 123457, $person2AttrJSON ), array( 123458, $person3AttrJSON ),
				array( '123456', $person1AttrJSON ), array( '123457', $person2AttrJSON ), array( '123458', $person3AttrJSON )
			)));


		/*********************
		 *
		 *      Act
		 *
		 *********************/
		try {
			$this->sutF1PeopleDataProvider->setupForPeopleSync();
		} catch ( Exception $e ) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$x = 'stop';
			throw $e;
		}

		$groups = $this->sutF1PeopleDataProvider->getGroups();
		$people = $this->sutF1PeopleDataProvider->getPeople();

		// assert groups
		$this->assertInternalType( 'array', $groups );
		$this->assertCount( 1, $groups );
		$this->assertArrayHasKey( 1235, $groups );
		$group = $groups[ 1235 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1235, $group->id() );
		$this->assertEquals( 'Staff', $group->getName() );
		$this->assertEquals( 'All full-time staff', $group->getDescription() );

		$this->assertInternalType( 'array', $people );
		$this->assertCount( 3, $people );
		$this->assertArrayHasKey( 123456, $people );
		$this->assertArrayHasKey( 123457, $people );
		$this->assertArrayHasKey( 123458, $people );

		// assert person 1
		$person1 = $people[123456];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person1 );
		$this->assertEquals( 123456, $person1->id() );
		$this->assertEquals( 'Dr Steve Jobs', $person1->getName( 'T F L' ) );
		$this->assertEquals( 'IT Manager', $person1->getPosition() );
		$this->assertTrue( $person1->syncPosition() );
		$this->assertEquals( '4211 1111', $person1->getPhone() );
		$this->assertTrue( $person1->syncPhone() );
		$this->assertEquals( 'person1@test.com', $person1->getEmail() );
		$this->assertTrue( $person1->syncEmail() );
		$this->assertEmpty( $person1->getURLs() );
		$person1Groups = $person1->getGroups();
		$this->assertCount( 1, $person1Groups );
		$firstGroup = reset( $person1Groups );
		$this->assertEquals( 1235, $firstGroup->id() );

		// assert person 2
		$person2 = $people[123457];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person2 );
		$this->assertEquals( 123457, $person2->id() );
		$this->assertEquals( 'Max Once', $person2->getName( 'F L' ) );
		$this->assertEquals( 'Senior Minister', $person2->getPosition() );
		$this->assertTrue( $person2->syncPosition() );
		$this->assertEquals( '0400 111 112', $person2->getPhone() );
		$this->assertTrue( $person2->syncPhone() );
		$this->assertEquals( 'person2@test.com', $person2->getEmail() );
		$this->assertTrue( $person2->syncEmail() );
		$this->assertEquals( "https://www.facebook.com/person2", $person2->getFacebookURL() );
		$this->assertTrue( $person2->syncFacebookURL() );
		$this->assertEmpty( $person2->getTwitterURL() );
		$this->assertEmpty( $person2->getLinkedInURL() );
		$person2Groups = $person2->getGroups();
		$this->assertCount( 1, $person2Groups );
		$firstGroup = reset( $person2Groups );
		$this->assertEquals( 1235, $firstGroup->id() );

		// assert person 3
		$person3 = $people[123458];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person3 );
		$this->assertEquals( 123458, $person3->id() );
		$this->assertEquals( 'Mr Johnathan "John" Edward Doe II', $person3->getName( 'T FQ M L S' ) );
		$this->assertEquals( 'Youth Minister, Social Media Manager', $person3->getPosition() );
		$this->assertTrue( $person3->syncPosition() );
		$this->assertEquals( '4299 1113', $person3->getPhone() );
		$this->assertTrue( $person3->syncPhone() );
		$this->assertEquals( 'person3_home@test.com', $person3->getEmail() );
		$this->assertTrue( $person3->syncEmail() );
		$this->assertEquals( "https://www.facebook.com/person3", $person3->getFacebookURL() );
		$this->assertTrue( $person3->syncFacebookURL() );
		$this->assertEquals( "https://twitter.com/person3", $person3->getTwitterURL() );
		$this->assertTrue( $person3->syncTwitterURL() );
		$this->assertEquals( "http://www.linkedin.com/in/person3", $person3->getLinkedInURL() );
		$this->assertTrue( $person3->syncLinkedInURL() );
		$person3Groups = $person3->getGroups();
		$this->assertCount( 1, $person3Groups );
		$firstGroup = reset( $person3Groups );
		$this->assertEquals( 1235, $firstGroup->id() );

	}

	public function testSetupForPeopleSync_3PeopleLists() {
		/*
		 * Sync groups true
		 * 3 people lists to sync, each with 2 people in it
		 * All sync options set to true
		 */
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPeopleGroups')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('getF1PeopleLists')
			->will($this->returnValue(array('Staff', 'Parish Council', 'Worship Team')));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPosition')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1PersonPositionAttribute')
			->will($this->returnValue('Church Position'));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPhone')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonEmail')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonFacebookURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonTwitterURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonLinkedInURL')
			->will($this->returnValue(true));

		// build a list of 5 groups from service provider
		$groupsJSON = $this->buildPeopleListsJSON( array(
			array(
				'id' => '1231',
				'name' => 'Staff',
				'description' => 'All full-time staff'
			), array(
				'id' => '1232',
				'name' => 'Members',
				'description' => 'All church members'
			), array(
				'id' => '1233',
				'name' => 'Worship Team',
			), array(
				'id' => '1234',
				'name' => 'Parish Council',
				'description' => 'Members of the parish council, who are responsible for administrative decisions.'
			), array(
				'id' => '1235',
				'name' => 'Ladies Morning Tea Group',
			)
		));
		// check that the test json data is well formed
		$this->assertNotNull( json_decode( $groupsJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleLists')
			->will($this->returnValue( $groupsJSON ));

		// these are the members of the Staff people list
		$staffMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1231', 'name' => 'Staff'
		), array(
			array(
				'id' => '12311',
				'person-id' => '1234511',
				'name' => 'Billy Graham'
			), array(
				'id' => '12312',
				'person-id' => '1234512',
				'name' => 'Rick Warren'
			)
		));
		$worshipTeamMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1233', 'name' => 'Worship Team'
		), array(
			array(
				'id' => '12331',
				'person-id' => '1234521',
				'name' => 'Jimmy Hand'
			), array(
				'id' => '12332',
				'person-id' => '1234522',
				'name' => 'Amy Grant'
			)
		));
		$parishCouncilMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1234', 'name' => 'Parish Council'
		), array(
			array(
				'id' => '12341',
				'person-id' => '1234531',
				'name' => 'John Doe'
			), array(
				'id' => '12342',
				'person-id' => '1234532',
				'name' => 'Sam Steward'
			)
		));
		$this->assertNotNull( json_decode( $staffMemberListJSON ) );
		$this->assertNotNull( json_decode( $worshipTeamMemberListJSON ) );
		$this->assertNotNull( json_decode( $parishCouncilMemberListJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleListMembers')
			->will($this->returnValueMap( array(
				array( '1231', $staffMemberListJSON ),
				array( '1233', $worshipTeamMemberListJSON ),
				array( '1234', $parishCouncilMemberListJSON ),
			)));

		// details of each person
		$person1JSON = $this->buildPersonJSON( array(
			'id' => '1234511',
			'title' => 'Dr',
			'firstName' => 'Billy',
			'lastName' => 'Graham'
		));
		$this->assertNotNull( json_decode( $person1JSON ) );
		$person2JSON = $this->buildPersonJSON( array(
			'id' => '1234512',
			'firstName' => 'Rick',
			'lastName' => 'Warren'
		));
		$this->assertNotNull( json_decode( $person2JSON ) );
		$person3JSON = $this->buildPersonJSON( array(
			'id' => '1234521',
			'firstName' => 'Jimmy',
			'lastName' => 'Hand'
		));
		$this->assertNotNull( json_decode( $person3JSON ) );
		$person4JSON = $this->buildPersonJSON( array(
			'id' => '1234522',
			'firstName' => 'Amy',
			'lastName' => 'Grant'
		));
		$this->assertNotNull( json_decode( $person4JSON ) );
		$person5JSON = $this->buildPersonJSON( array(
			'id' => '1234531',
			'title' => 'Mr',
			'firstName' => 'Johnathan',
			'lastName' => 'Doe',
			'middleName' => 'Edward',
			'goesByName' => 'John',
			'suffix' => 'II'
		));
		$this->assertNotNull( json_decode( $person5JSON ) );
		$person6JSON = $this->buildPersonJSON( array(
			'id' => '1234532',
			'title' => 'Mr',
			'firstName' => 'Samuel',
			'lastName' => 'Steward',
			'middleName' => 'Joseph',
			'goesByName' => 'Sam'
		));
		$this->assertNotNull( json_decode( $person6JSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPerson')
			->will($this->returnValueMap(array(
				array( 1234511, $person1JSON ), array( 1234512, $person2JSON ),
				array( 1234521, $person3JSON ), array( 1234522, $person4JSON ),
				array( 1234531, $person5JSON ), array( 1234532, $person6JSON ),
				array( '1234511', $person1JSON ), array( '1234512', $person2JSON ),
				array( '1234521', $person3JSON ), array( '1234522', $person4JSON ),
				array( '1234531', $person5JSON ), array( '1234532', $person6JSON ),
			)));

		// add communications data for each person
		$person1CommJSON = $this->buildCommunicationsJSON( '1234511', array(
			array(
				'id' => '12345111',
				'type' => 'Home Phone',
				'value' => '(01) 1111 1111',
				'preferred' => true
			),
			array(
				'id' => '12345112',
				'type' => 'Email',
				'value' => 'person1@test.com',
				'preferred' => true
			)
		));
		$this->assertNotNull( json_decode( $person1CommJSON ) );
		$person2CommJSON = $this->buildCommunicationsJSON( '1234512', array(
			array(
				'id' => '12345121',
				'type' => 'Mobile Phone',
				'value' => '0400 111 121',
				'preferred' => true
			), array(
				'id' => '12345122',
				'type' => 'Home Email',
				'value' => 'person2_home@test.com',
				'preferred' => true
			), array(
				'id' => '12345123',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person2',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person2CommJSON ) );
		$person3CommJSON = $this->buildCommunicationsJSON( '1234521', array(
			array(
				'id' => '12345211',
				'type' => 'Mobile Phone',
				'value' => '0400 111 211',
				'preferred' => true
			), array(
				'id' => '12345212',
				'type' => 'Work Phone',
				'value' => '4299 1212',
				'preferred' => false
			), array(
				'id' => '12345213',
				'type' => 'Work Email',
				'value' => 'person3_work@test.com',
				'preferred' => true
			), array(
				'id' => '12345214',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person3',
				'preferred' => false
			), array(
				'id' => '12345215',
				'type' => 'Linked-In',
				'value' => 'http://www.linkedin.com/in/person3',
				'preferred' => false
			), array(
				'id' => '12345216',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person3',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person3CommJSON ) );
		$person4CommJSON = $this->buildCommunicationsJSON( '1234522', array(
			array(
				'id' => '12345221',
				'type' => 'Work Phone',
				'value' => '4299 1221',
				'preferred' => true
			), array(
				'id' => '12345222',
				'type' => 'Email',
				'value' => 'person4@test.com',
				'preferred' => true
			), array(
				'id' => '12345123',
				'type' => 'Linked-In',
				'value' => 'https://www.linkedin.com/in/person4',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person4CommJSON ) );
		$person5CommJSON = $this->buildCommunicationsJSON( '1234531', array(
			array(
				'id' => '12345311',
				'type' => 'Home Phone',
				'value' => '4299 1311',
				'preferred' => false
			), array(
				'id' => '12345312',
				'type' => 'Mobile Phone',
				'value' => '0400 111 312',
				'preferred' => true
			), array(
				'id' => '12345313',
				'type' => 'Email',
				'value' => 'person5@test.com',
				'preferred' => true
			), array(
				'id' => '12345314',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person5',
				'preferred' => false
			), array(
				'id' => '12345315',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person5',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person5CommJSON ) );
		$person6CommJSON = $this->buildCommunicationsJSON( '1234532', array(
			array(
				'id' => '12345321',
				'type' => 'Home Phone',
				'value' => '(01) 4211 1321',
				'preferred' => true
			), array(
				'id' => '12345322',
				'type' => 'Mobile Phone',
				'value' => '0400 111 322',
				'preferred' => false
			), array(
				'id' => '12345323',
				'type' => 'Email',
				'value' => 'person6@test.com',
				'preferred' => false
			), array(
				'id' => '12345324',
				'type' => 'Home Email',
				'value' => 'person6@home.com',
				'preferred' => true
			), array(
				'id' => '12345325',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person6',
				'preferred' => false
			), array(
				'id' => '12345326',
				'type' => 'Linked-In',
				'value' => 'https://www.linkedin.com/in/person6',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person6CommJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonCommunications')
			->will($this->returnValueMap(array(
				array( 1234511, $person1CommJSON ), array( 1234512, $person2CommJSON ),
				array( 1234521, $person3CommJSON ), array( 1234522, $person4CommJSON ),
				array( 1234531, $person5CommJSON ), array( 1234532, $person6CommJSON ),
				array( '1234511', $person1CommJSON ), array( '1234512', $person2CommJSON ),
				array( '1234521', $person3CommJSON ), array( '1234522', $person4CommJSON ),
				array( '1234531', $person5CommJSON ), array( '1234532', $person6CommJSON ),
			)));

		// add attributes for position, with attribute name being 'Church Position'
		$person1AttrJSON = $this->buildPersonAttributesJSON( '1234511', array(
			array(
				'id' => '12345111',
				'group-id' => '112',
				'group-attr-id' => '1121',
				'group-name' => 'Not Church Position',
				'attr-name' => 'Some other attribute'
			), array(
				'id' => '12345112',
				'group-id' => '111',
				'group-attr-id' => '1111',
				'group-name' => 'Church Position',
				'attr-name' => 'Senior Minister'
			),
		));
		$this->assertNotNull( json_decode( $person1AttrJSON ) );
		$person2AttrJSON = $this->buildPersonAttributesJSON( '1234512', array(
			array(
				'id' => '12345121',
				'group-id' => '111',
				'group-attr-id' => '1112',
				'group-name' => 'Church Position',
				'attr-name' => 'Assistant Minister'
			)
		));
		$this->assertNotNull( json_decode( $person2AttrJSON ) );
		$person3AttrJSON = $this->buildPersonAttributesJSON( '1234521', array(
			array(
				'id' => '12345211',
				'group-id' => '111',
				'group-attr-id' => '1113',
				'group-name' => 'Church Position',
				'attr-name' => 'Worship Leader'
			)
		));
		$this->assertNotNull( json_decode( $person3AttrJSON ) );
		$person4AttrJSON = $this->buildPersonAttributesJSON( '1234522', array(
			array(
				'id' => '12345221',
				'group-id' => '111',
				'group-attr-id' => '1114',
				'group-name' => 'Church Position',
				'attr-name' => 'Vocalist'
			)
		));
		$this->assertNotNull( json_decode( $person4AttrJSON ) );
		$person5AttrJSON = $this->buildPersonAttributesJSON( '1234531', array(
			array(
				'id' => '12345311',
				'group-id' => '111',
				'group-attr-id' => '1115',
				'group-name' => 'Church Position',
				'attr-name' => 'Parish Councillor'
			), array(
				'id' => '12345321',
				'group-id' => '211',
				'group-attr-id' => '2111',
				'group-name' => 'Another Attribute Group',
				'attr-name' => 'Another Attribute'
			)
		));
		$this->assertNotNull( json_decode( $person5AttrJSON ) );
		$person6AttrJSON = $this->buildPersonAttributesJSON( '1234532', array(
			array(
				'id' => '12345321',
				'group-id' => '111',
				'group-attr-id' => '1115',
				'group-name' => 'Church Position',
				'attr-name' => 'Parish Councillor'
			)
		));
		$this->assertNotNull( json_decode( $person6AttrJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonAttributes')
			->will($this->returnValueMap(array(
				array( 1234511, $person1AttrJSON ), array( 1234512, $person2AttrJSON ),
				array( 1234521, $person3AttrJSON ), array( 1234522, $person4AttrJSON ),
				array( 1234531, $person5AttrJSON ), array( 1234532, $person6AttrJSON ),
				array( '1234511', $person1AttrJSON ), array( '1234512', $person2AttrJSON ),
				array( '1234521', $person3AttrJSON ), array( '1234522', $person4AttrJSON ),
				array( '1234531', $person5AttrJSON ), array( '1234532', $person6AttrJSON ),
			)));


		/*********************
		 *
		 *      Act
		 *
		 *********************/
		try {
			$this->sutF1PeopleDataProvider->setupForPeopleSync();
		} catch ( Exception $e ) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$x = 'stop';
			throw $e;
		}

		$groups = $this->sutF1PeopleDataProvider->getGroups();
		$people = $this->sutF1PeopleDataProvider->getPeople();

		// assert groups
		$this->assertInternalType( 'array', $groups );
		$this->assertCount( 3, $groups );
		// group 1
		$this->assertArrayHasKey( 1231, $groups );
		$group = $groups[ 1231 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1231, $group->id() );
		$this->assertEquals( 'Staff', $group->getName() );
		$this->assertEquals( 'All full-time staff', $group->getDescription() );
		// group 2
		$this->assertArrayHasKey( 1233, $groups );
		$group = $groups[ 1233 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1233, $group->id() );
		$this->assertEquals( 'Worship Team', $group->getName() );
		$this->assertEmpty( $group->getDescription() );
		// group 3
		$this->assertArrayHasKey( 1234, $groups );
		$group = $groups[ 1234 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1234, $group->id() );
		$this->assertEquals( 'Parish Council', $group->getName() );
		$this->assertEquals( 'Members of the parish council, who are responsible for administrative decisions.', $group->getDescription() );

		$this->assertInternalType( 'array', $people );
		$this->assertCount( 6, $people );
		$this->assertArrayHasKey( 1234511, $people );
		$this->assertArrayHasKey( 1234512, $people );
		$this->assertArrayHasKey( 1234521, $people );
		$this->assertArrayHasKey( 1234522, $people );
		$this->assertArrayHasKey( 1234531, $people );
		$this->assertArrayHasKey( 1234532, $people );

		// assert person 1
		$person1 = $people[1234511];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person1 );
		$this->assertEquals( 1234511, $person1->id() );
		$this->assertEquals( 'Dr Billy Graham', $person1->getName( 'T F L' ) );
		$this->assertEquals( 'Senior Minister', $person1->getPosition() );
		$this->assertTrue( $person1->syncPosition() );
		$this->assertEquals( '(01) 1111 1111', $person1->getPhone() );
		$this->assertTrue( $person1->syncPhone() );
		$this->assertEquals( 'person1@test.com', $person1->getEmail() );
		$this->assertTrue( $person1->syncEmail() );
		$this->assertEmpty( $person1->getURLs() );
		$person1Groups = $person1->getGroups();
		$this->assertCount( 1, $person1Groups );
		$firstGroup = reset( $person1Groups );
		$this->assertEquals( 1231, $firstGroup->id() );

		// assert person 2
		$person2 = $people[1234512];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person2 );
		$this->assertEquals( 1234512, $person2->id() );
		$this->assertEquals( 'Rick Warren', $person2->getName( 'F L' ) );
		$this->assertEquals( 'Assistant Minister', $person2->getPosition() );
		$this->assertTrue( $person2->syncPosition() );
		$this->assertEquals( '0400 111 121', $person2->getPhone() );
		$this->assertTrue( $person2->syncPhone() );
		$this->assertEquals( 'person2_home@test.com', $person2->getEmail() );
		$this->assertTrue( $person2->syncEmail() );
		$this->assertEquals( "https://www.facebook.com/person2", $person2->getFacebookURL() );
		$this->assertTrue( $person2->syncFacebookURL() );
		$this->assertEmpty( $person2->getTwitterURL() );
		$this->assertEmpty( $person2->getLinkedInURL() );
		$person2Groups = $person2->getGroups();
		$this->assertCount( 1, $person2Groups );
		$firstGroup = reset( $person2Groups );
		$this->assertEquals( 1231, $firstGroup->id() );

		// assert person 3
		$person3 = $people[1234521];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person3 );
		$this->assertEquals( 1234521, $person3->id() );
		$this->assertEquals( 'Jimmy Hand', $person3->getName( 'F L' ) );
		$this->assertEquals( 'Worship Leader', $person3->getPosition() );
		$this->assertTrue( $person3->syncPosition() );
		$this->assertEquals( '0400 111 211', $person3->getPhone() );
		$this->assertTrue( $person3->syncPhone() );
		$this->assertEquals( 'person3_work@test.com', $person3->getEmail() );
		$this->assertTrue( $person3->syncEmail() );
		$this->assertEquals( "https://www.facebook.com/person3", $person3->getFacebookURL() );
		$this->assertTrue( $person3->syncFacebookURL() );
		$this->assertEquals( "https://twitter.com/person3", $person3->getTwitterURL() );
		$this->assertTrue( $person3->syncTwitterURL() );
		$this->assertEquals( "http://www.linkedin.com/in/person3", $person3->getLinkedInURL() );
		$this->assertTrue( $person3->syncLinkedInURL() );
		$person3Groups = $person3->getGroups();
		$this->assertCount( 1, $person3Groups );
		$firstGroup = reset( $person3Groups );
		$this->assertEquals( 1233, $firstGroup->id() );

		// assert person 4
		$person4 = $people[1234522];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person4 );
		$this->assertEquals( 1234522, $person4->id() );
		$this->assertEquals( 'Amy Grant', $person4->getName( 'F L' ) );
		$this->assertEquals( 'Vocalist', $person4->getPosition() );
		$this->assertTrue( $person4->syncPosition() );
		$this->assertEquals( '4299 1221', $person4->getPhone() );
		$this->assertTrue( $person4->syncPhone() );
		$this->assertEquals( 'person4@test.com', $person4->getEmail() );
		$this->assertTrue( $person4->syncEmail() );
		$this->assertEmpty( $person4->getFacebookURL() );
		$this->assertEmpty( $person4->getTwitterURL() );
		$this->assertEquals( "https://www.linkedin.com/in/person4", $person4->getLinkedInURL() );
		$this->assertTrue( $person4->syncLinkedInURL() );
		$person4Groups = $person4->getGroups();
		$this->assertCount( 1, $person4Groups );
		$firstGroup = reset( $person4Groups );
		$this->assertEquals( 1233, $firstGroup->id() );

		// assert person 5
		$person5 = $people[1234531];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person5 );
		$this->assertEquals( 1234531, $person5->id() );
		$this->assertEquals( 'Mr Johnathan "John" Edward Doe II', $person5->getName( 'T FQ M L S' ) );
		$this->assertEquals( 'Parish Councillor', $person5->getPosition() );
		$this->assertTrue( $person5->syncPosition() );
		$this->assertEquals( '0400 111 312', $person5->getPhone() );
		$this->assertTrue( $person5->syncPhone() );
		$this->assertEquals( 'person5@test.com', $person5->getEmail() );
		$this->assertTrue( $person5->syncEmail() );
		$this->assertEquals( 'https://www.facebook.com/person5', $person5->getFacebookURL() );
		$this->assertTrue( $person5->syncFacebookURL() );
		$this->assertEmpty( $person5->getLinkedInURL() );
		$this->assertEquals( "https://twitter.com/person5", $person5->getTwitterURL() );
		$this->assertTrue( $person5->syncTwitterURL() );
		$person5Groups = $person5->getGroups();
		$this->assertCount( 1, $person5Groups );
		$firstGroup = reset( $person5Groups );
		$this->assertEquals( 1234, $firstGroup->id() );

		// assert person 6
		$person6 = $people[1234532];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person6 );
		$this->assertEquals( 1234532, $person6->id() );
		$this->assertEquals( 'Mr Samuel "Sam" Joseph Steward', $person6->getName( 'T FQ M L' ) );
		$this->assertEquals( 'Parish Councillor', $person6->getPosition() );
		$this->assertTrue( $person6->syncPosition() );
		$this->assertEquals( '(01) 4211 1321', $person6->getPhone() );
		$this->assertTrue( $person6->syncPhone() );
		$this->assertEquals( 'person6@home.com', $person6->getEmail() );
		$this->assertTrue( $person6->syncEmail() );
		$this->assertEquals( 'https://www.facebook.com/person6', $person6->getFacebookURL() );
		$this->assertTrue( $person6->syncFacebookURL() );
		$this->assertEmpty( $person6->getTwitterURL() );
		$this->assertEquals( "https://www.linkedin.com/in/person6", $person6->getLinkedInURL() );
		$this->assertTrue( $person6->syncLinkedInURL() );
		$person6Groups = $person6->getGroups();
		$this->assertCount( 1, $person6Groups );
		$firstGroup = reset( $person6Groups );
		$this->assertEquals( 1234, $firstGroup->id() );

	}

	public function testSetupForPeopleSync_3PeopleLists_1PersonIn2Groups() {
		/*
		 * Sync groups true
		 * 3 people lists to sync, each with 2 people in it
		 * All sync options set to true
		 */
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPeopleGroups')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('getF1PeopleLists')
			->will($this->returnValue(array('Staff', 'Parish Council', 'Worship Team')));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPosition')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1PersonPositionAttribute')
			->will($this->returnValue('Church Position'));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPhone')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonEmail')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonFacebookURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonTwitterURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonLinkedInURL')
			->will($this->returnValue(true));

		// build a list of 5 groups from service provider
		$groupsJSON = $this->buildPeopleListsJSON( array(
			array(
				'id' => '1231',
				'name' => 'Staff',
				'description' => 'All full-time staff'
			), array(
				'id' => '1232',
				'name' => 'Members',
				'description' => 'All church members'
			), array(
				'id' => '1233',
				'name' => 'Worship Team',
			), array(
				'id' => '1234',
				'name' => 'Parish Council',
				'description' => 'Members of the parish council, who are responsible for administrative decisions.'
			), array(
				'id' => '1235',
				'name' => 'Ladies Morning Tea Group',
			)
		));
		// check that the test json data is well formed
		$this->assertNotNull( json_decode( $groupsJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleLists')
			->will($this->returnValue( $groupsJSON ));

		// these are the members of the Staff people list
		$staffMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1231', 'name' => 'Staff'
		), array(
			array(
				'id' => '12311',
				'person-id' => '1234511',
				'name' => 'Billy Graham'
			), array(
				'id' => '12312',
				'person-id' => '1234512',
				'name' => 'Rick Warren'
			)
		));
		$worshipTeamMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1233', 'name' => 'Worship Team'
		), array(
			array(
				'id' => '12331',
				'person-id' => '1234521',
				'name' => 'Jimmy Hand'
			), array(
				'id' => '12332',
				'person-id' => '1234522',
				'name' => 'Amy Grant'
			)
		));
		$parishCouncilMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1234', 'name' => 'Parish Council'
		), array(
			array(
				'id' => '12341',
				'person-id' => '1234531',
				'name' => 'John Doe'
			), array(
				'id' => '12342',
				'person-id' => '1234532',
				'name' => 'Sam Steward'
			), array(
				'id' => '12343',
				'person-id' => '1234521',
				'name' => 'Jimmy Hand'
			),
		));
		$this->assertNotNull( json_decode( $staffMemberListJSON ) );
		$this->assertNotNull( json_decode( $worshipTeamMemberListJSON ) );
		$this->assertNotNull( json_decode( $parishCouncilMemberListJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleListMembers')
			->will($this->returnValueMap( array(
				array( '1231', $staffMemberListJSON ),
				array( '1233', $worshipTeamMemberListJSON ),
				array( '1234', $parishCouncilMemberListJSON ),
			)));

		// details of each person
		$person1JSON = $this->buildPersonJSON( array(
			'id' => '1234511',
			'title' => 'Dr',
			'firstName' => 'Billy',
			'lastName' => 'Graham'
		));
		$this->assertNotNull( json_decode( $person1JSON ) );
		$person2JSON = $this->buildPersonJSON( array(
			'id' => '1234512',
			'firstName' => 'Rick',
			'lastName' => 'Warren'
		));
		$this->assertNotNull( json_decode( $person2JSON ) );
		$person3JSON = $this->buildPersonJSON( array(
			'id' => '1234521',
			'firstName' => 'Jimmy',
			'lastName' => 'Hand'
		));
		$this->assertNotNull( json_decode( $person3JSON ) );
		$person4JSON = $this->buildPersonJSON( array(
			'id' => '1234522',
			'firstName' => 'Amy',
			'lastName' => 'Grant'
		));
		$this->assertNotNull( json_decode( $person4JSON ) );
		$person5JSON = $this->buildPersonJSON( array(
			'id' => '1234531',
			'title' => 'Mr',
			'firstName' => 'Johnathan',
			'lastName' => 'Doe',
			'middleName' => 'Edward',
			'goesByName' => 'John',
			'suffix' => 'II'
		));
		$this->assertNotNull( json_decode( $person5JSON ) );
		$person6JSON = $this->buildPersonJSON( array(
			'id' => '1234532',
			'title' => 'Mr',
			'firstName' => 'Samuel',
			'lastName' => 'Steward',
			'middleName' => 'Joseph',
			'goesByName' => 'Sam'
		));
		$this->assertNotNull( json_decode( $person6JSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPerson')
			->will($this->returnValueMap(array(
				array( 1234511, $person1JSON ), array( 1234512, $person2JSON ),
				array( 1234521, $person3JSON ), array( 1234522, $person4JSON ),
				array( 1234531, $person5JSON ), array( 1234532, $person6JSON ),
				array( '1234511', $person1JSON ), array( '1234512', $person2JSON ),
				array( '1234521', $person3JSON ), array( '1234522', $person4JSON ),
				array( '1234531', $person5JSON ), array( '1234532', $person6JSON ),
			)));

		// add communications data for each person
		$person1CommJSON = $this->buildCommunicationsJSON( '1234511', array(
			array(
				'id' => '12345111',
				'type' => 'Home Phone',
				'value' => '(01) 1111 1111',
				'preferred' => true
			),
			array(
				'id' => '12345112',
				'type' => 'Email',
				'value' => 'person1@test.com',
				'preferred' => true
			)
		));
		$this->assertNotNull( json_decode( $person1CommJSON ) );
		$person2CommJSON = $this->buildCommunicationsJSON( '1234512', array(
			array(
				'id' => '12345121',
				'type' => 'Mobile Phone',
				'value' => '0400 111 121',
				'preferred' => true
			), array(
				'id' => '12345122',
				'type' => 'Home Email',
				'value' => 'person2_home@test.com',
				'preferred' => true
			), array(
				'id' => '12345123',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person2',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person2CommJSON ) );
		$person3CommJSON = $this->buildCommunicationsJSON( '1234521', array(
			array(
				'id' => '12345211',
				'type' => 'Mobile Phone',
				'value' => '0400 111 211',
				'preferred' => true
			), array(
				'id' => '12345212',
				'type' => 'Work Phone',
				'value' => '4299 1212',
				'preferred' => false
			), array(
				'id' => '12345213',
				'type' => 'Work Email',
				'value' => 'person3_work@test.com',
				'preferred' => true
			), array(
				'id' => '12345214',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person3',
				'preferred' => false
			), array(
				'id' => '12345215',
				'type' => 'Linked-In',
				'value' => 'http://www.linkedin.com/in/person3',
				'preferred' => false
			), array(
				'id' => '12345216',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person3',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person3CommJSON ) );
		$person4CommJSON = $this->buildCommunicationsJSON( '1234522', array(
			array(
				'id' => '12345221',
				'type' => 'Work Phone',
				'value' => '4299 1221',
				'preferred' => true
			), array(
				'id' => '12345222',
				'type' => 'Email',
				'value' => 'person4@test.com',
				'preferred' => true
			), array(
				'id' => '12345123',
				'type' => 'Linked-In',
				'value' => 'https://www.linkedin.com/in/person4',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person4CommJSON ) );
		$person5CommJSON = $this->buildCommunicationsJSON( '1234531', array(
			array(
				'id' => '12345311',
				'type' => 'Home Phone',
				'value' => '4299 1311',
				'preferred' => false
			), array(
				'id' => '12345312',
				'type' => 'Mobile Phone',
				'value' => '0400 111 312',
				'preferred' => true
			), array(
				'id' => '12345313',
				'type' => 'Email',
				'value' => 'person5@test.com',
				'preferred' => true
			), array(
				'id' => '12345314',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person5',
				'preferred' => false
			), array(
				'id' => '12345315',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person5',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person5CommJSON ) );
		$person6CommJSON = $this->buildCommunicationsJSON( '1234532', array(
			array(
				'id' => '12345321',
				'type' => 'Home Phone',
				'value' => '(01) 4211 1321',
				'preferred' => true
			), array(
				'id' => '12345322',
				'type' => 'Mobile Phone',
				'value' => '0400 111 322',
				'preferred' => false
			), array(
				'id' => '12345323',
				'type' => 'Email',
				'value' => 'person6@test.com',
				'preferred' => false
			), array(
				'id' => '12345324',
				'type' => 'Home Email',
				'value' => 'person6@home.com',
				'preferred' => true
			), array(
				'id' => '12345325',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person6',
				'preferred' => false
			), array(
				'id' => '12345326',
				'type' => 'Linked-In',
				'value' => 'https://www.linkedin.com/in/person6',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person6CommJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonCommunications')
			->will($this->returnValueMap(array(
				array( 1234511, $person1CommJSON ), array( 1234512, $person2CommJSON ),
				array( 1234521, $person3CommJSON ), array( 1234522, $person4CommJSON ),
				array( 1234531, $person5CommJSON ), array( 1234532, $person6CommJSON ),
				array( '1234511', $person1CommJSON ), array( '1234512', $person2CommJSON ),
				array( '1234521', $person3CommJSON ), array( '1234522', $person4CommJSON ),
				array( '1234531', $person5CommJSON ), array( '1234532', $person6CommJSON ),
			)));

		// add attributes for position, with attribute name being 'Church Position'
		$person1AttrJSON = $this->buildPersonAttributesJSON( '1234511', array(
			array(
				'id' => '12345111',
				'group-id' => '112',
				'group-attr-id' => '1121',
				'group-name' => 'Not Church Position',
				'attr-name' => 'Some other attribute'
			), array(
				'id' => '12345112',
				'group-id' => '111',
				'group-attr-id' => '1111',
				'group-name' => 'Church Position',
				'attr-name' => 'Senior Minister'
			),
		));
		$this->assertNotNull( json_decode( $person1AttrJSON ) );
		$person2AttrJSON = $this->buildPersonAttributesJSON( '1234512', array(
			array(
				'id' => '12345121',
				'group-id' => '111',
				'group-attr-id' => '1112',
				'group-name' => 'Church Position',
				'attr-name' => 'Assistant Minister'
			)
		));
		$this->assertNotNull( json_decode( $person2AttrJSON ) );
		$person3AttrJSON = $this->buildPersonAttributesJSON( '1234521', array(
			array(
				'id' => '12345211',
				'group-id' => '111',
				'group-attr-id' => '1113',
				'group-name' => 'Church Position',
				'attr-name' => 'Worship Leader'
			)
		));
		$this->assertNotNull( json_decode( $person3AttrJSON ) );
		$person4AttrJSON = $this->buildPersonAttributesJSON( '1234522', array(
			array(
				'id' => '12345221',
				'group-id' => '111',
				'group-attr-id' => '1114',
				'group-name' => 'Church Position',
				'attr-name' => 'Vocalist'
			)
		));
		$this->assertNotNull( json_decode( $person4AttrJSON ) );
		$person5AttrJSON = $this->buildPersonAttributesJSON( '1234531', array(
			array(
				'id' => '12345311',
				'group-id' => '111',
				'group-attr-id' => '1115',
				'group-name' => 'Church Position',
				'attr-name' => 'Parish Councillor'
			), array(
				'id' => '12345321',
				'group-id' => '211',
				'group-attr-id' => '2111',
				'group-name' => 'Another Attribute Group',
				'attr-name' => 'Another Attribute'
			)
		));
		$this->assertNotNull( json_decode( $person5AttrJSON ) );
		$person6AttrJSON = $this->buildPersonAttributesJSON( '1234532', array(
			array(
				'id' => '12345321',
				'group-id' => '111',
				'group-attr-id' => '1115',
				'group-name' => 'Church Position',
				'attr-name' => 'Parish Councillor'
			)
		));
		$this->assertNotNull( json_decode( $person6AttrJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonAttributes')
			->will($this->returnValueMap(array(
				array( 1234511, $person1AttrJSON ), array( 1234512, $person2AttrJSON ),
				array( 1234521, $person3AttrJSON ), array( 1234522, $person4AttrJSON ),
				array( 1234531, $person5AttrJSON ), array( 1234532, $person6AttrJSON ),
				array( '1234511', $person1AttrJSON ), array( '1234512', $person2AttrJSON ),
				array( '1234521', $person3AttrJSON ), array( '1234522', $person4AttrJSON ),
				array( '1234531', $person5AttrJSON ), array( '1234532', $person6AttrJSON ),
			)));


		/*********************
		 *
		 *      Act
		 *
		 *********************/
		try {
			$this->sutF1PeopleDataProvider->setupForPeopleSync();
		} catch ( Exception $e ) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$x = 'stop';
			throw $e;
		}

		$groups = $this->sutF1PeopleDataProvider->getGroups();
		$people = $this->sutF1PeopleDataProvider->getPeople();

		// assert groups
		$this->assertInternalType( 'array', $groups );
		$this->assertCount( 3, $groups );
		// group 1
		$this->assertArrayHasKey( 1231, $groups );
		$group = $groups[ 1231 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1231, $group->id() );
		$this->assertEquals( 'Staff', $group->getName() );
		$this->assertEquals( 'All full-time staff', $group->getDescription() );
		// group 2
		$this->assertArrayHasKey( 1233, $groups );
		$group = $groups[ 1233 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1233, $group->id() );
		$this->assertEquals( 'Worship Team', $group->getName() );
		$this->assertEmpty( $group->getDescription() );
		// group 3
		$this->assertArrayHasKey( 1234, $groups );
		$group = $groups[ 1234 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1234, $group->id() );
		$this->assertEquals( 'Parish Council', $group->getName() );
		$this->assertEquals( 'Members of the parish council, who are responsible for administrative decisions.', $group->getDescription() );

		$this->assertInternalType( 'array', $people );
		$this->assertCount( 6, $people );
		$this->assertArrayHasKey( 1234511, $people );
		$this->assertArrayHasKey( 1234512, $people );
		$this->assertArrayHasKey( 1234521, $people );
		$this->assertArrayHasKey( 1234522, $people );
		$this->assertArrayHasKey( 1234531, $people );
		$this->assertArrayHasKey( 1234532, $people );

		// assert person 3
		$person3 = $people[1234521];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person3 );
		$this->assertEquals( 1234521, $person3->id() );
		$this->assertEquals( 'Jimmy Hand', $person3->getName( 'F L' ) );
		$this->assertEquals( 'Worship Leader', $person3->getPosition() );
		$this->assertTrue( $person3->syncPosition() );
		// ************* assert both groups assigned *********************
		/** @var CTCI_PeopleGroupInterface[] $person3Groups */
		$person3Groups = $person3->getGroups();
		$this->assertCount( 2, $person3Groups );
		// check person is in both groups while not tying the test to array index values
		$in1233 = false;
		$in1234 = false;
		foreach ( $person3Groups as $group ) {
			$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
			if ( $group->id() == 1233 ) {
				$in1233 = true;
			}
			if ( $group->id() == 1234 ) {
				$in1234 = true;
			}
		}
		$this->assertTrue( $in1233 && $in1234 );
	}

	public function testSetupForPeopleSync_3PeopleLists_SyncOptionsOff() {
		/*
		 * Sync groups true
		 * 3 people lists to sync, incl. one person in 2 lists
		 * all sync options off
		 */
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPeopleGroups')
			->will($this->returnValue(false));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('getF1PeopleLists')
			->will($this->returnValue(array('Staff', 'Parish Council', 'Worship Team')));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPosition')
			->will($this->returnValue(false));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1PersonPositionAttribute')
			->will($this->returnValue(null));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPhone')
			->will($this->returnValue(false));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonEmail')
			->will($this->returnValue(false));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonFacebookURL')
			->will($this->returnValue(false));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonTwitterURL')
			->will($this->returnValue(false));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonLinkedInURL')
			->will($this->returnValue(false));

		// build a list of 5 groups from service provider
		$groupsJSON = $this->buildPeopleListsJSON( array(
			array(
				'id' => '1231',
				'name' => 'Staff',
				'description' => 'All full-time staff'
			), array(
				'id' => '1232',
				'name' => 'Members',
				'description' => 'All church members'
			), array(
				'id' => '1233',
				'name' => 'Worship Team',
			), array(
				'id' => '1234',
				'name' => 'Parish Council',
				'description' => 'Members of the parish council, who are responsible for administrative decisions.'
			), array(
				'id' => '1235',
				'name' => 'Ladies Morning Tea Group',
			)
		));
		// check that the test json data is well formed
		$this->assertNotNull( json_decode( $groupsJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleLists')
			->will($this->returnValue( $groupsJSON ));

		// these are the members of the Staff people list
		$staffMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1231', 'name' => 'Staff'
		), array(
			array(
				'id' => '12311',
				'person-id' => '1234511',
				'name' => 'Billy Graham'
			), array(
				'id' => '12312',
				'person-id' => '1234512',
				'name' => 'Rick Warren'
			)
		));
		$worshipTeamMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1233', 'name' => 'Worship Team'
		), array(
			array(
				'id' => '12331',
				'person-id' => '1234521',
				'name' => 'Jimmy Hand'
			), array(
				'id' => '12332',
				'person-id' => '1234522',
				'name' => 'Amy Grant'
			)
		));
		$parishCouncilMemberListJSON = $this->buildMemberListJSON( array(
			'id' => '1234', 'name' => 'Parish Council'
		), array(
			array(
				'id' => '12341',
				'person-id' => '1234531',
				'name' => 'John Doe'
			), array(
				'id' => '12342',
				'person-id' => '1234532',
				'name' => 'Sam Steward'
			), array(
				'id' => '12343',
				'person-id' => '1234521',
				'name' => 'Jimmy Hand'
			)
		));
		$this->assertNotNull( json_decode( $staffMemberListJSON ) );
		$this->assertNotNull( json_decode( $worshipTeamMemberListJSON ) );
		$this->assertNotNull( json_decode( $parishCouncilMemberListJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleListMembers')
			->will($this->returnValueMap( array(
				array( '1231', $staffMemberListJSON ),
				array( '1233', $worshipTeamMemberListJSON ),
				array( '1234', $parishCouncilMemberListJSON ),
			)));

		// details of each person
		$person1JSON = $this->buildPersonJSON( array(
			'id' => '1234511',
			'title' => 'Dr',
			'firstName' => 'Billy',
			'lastName' => 'Graham'
		));
		$this->assertNotNull( json_decode( $person1JSON ) );
		$person2JSON = $this->buildPersonJSON( array(
			'id' => '1234512',
			'firstName' => 'Rick',
			'lastName' => 'Warren'
		));
		$this->assertNotNull( json_decode( $person2JSON ) );
		$person3JSON = $this->buildPersonJSON( array(
			'id' => '1234521',
			'firstName' => 'Jimmy',
			'lastName' => 'Hand'
		));
		$this->assertNotNull( json_decode( $person3JSON ) );
		$person4JSON = $this->buildPersonJSON( array(
			'id' => '1234522',
			'firstName' => 'Amy',
			'lastName' => 'Grant'
		));
		$this->assertNotNull( json_decode( $person4JSON ) );
		$person5JSON = $this->buildPersonJSON( array(
			'id' => '1234531',
			'title' => 'Mr',
			'firstName' => 'Johnathan',
			'lastName' => 'Doe',
			'middleName' => 'Edward',
			'goesByName' => 'John',
			'suffix' => 'II'
		));
		$this->assertNotNull( json_decode( $person5JSON ) );
		$person6JSON = $this->buildPersonJSON( array(
			'id' => '1234532',
			'title' => 'Mr',
			'firstName' => 'Samuel',
			'lastName' => 'Steward',
			'middleName' => 'Joseph',
			'goesByName' => 'Sam'
		));
		$this->assertNotNull( json_decode( $person6JSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPerson')
			->will($this->returnValueMap(array(
				array( 1234511, $person1JSON ), array( 1234512, $person2JSON ),
				array( 1234521, $person3JSON ), array( 1234522, $person4JSON ),
				array( 1234531, $person5JSON ), array( 1234532, $person6JSON ),
				array( '1234511', $person1JSON ), array( '1234512', $person2JSON ),
				array( '1234521', $person3JSON ), array( '1234522', $person4JSON ),
				array( '1234531', $person5JSON ), array( '1234532', $person6JSON ),
			)));

		// add communications data for each person
		$person1CommJSON = $this->buildCommunicationsJSON( '1234511', array(
			array(
				'id' => '12345111',
				'type' => 'Home Phone',
				'value' => '(01) 1111 1111',
				'preferred' => true
			),
			array(
				'id' => '12345112',
				'type' => 'Email',
				'value' => 'person1@test.com',
				'preferred' => true
			)
		));
		$this->assertNotNull( json_decode( $person1CommJSON ) );
		$person2CommJSON = $this->buildCommunicationsJSON( '1234512', array(
			array(
				'id' => '12345121',
				'type' => 'Mobile Phone',
				'value' => '0400 111 121',
				'preferred' => true
			), array(
				'id' => '12345122',
				'type' => 'Home Email',
				'value' => 'person2_home@test.com',
				'preferred' => true
			), array(
				'id' => '12345123',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person2',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person2CommJSON ) );
		$person3CommJSON = $this->buildCommunicationsJSON( '1234521', array(
			array(
				'id' => '12345211',
				'type' => 'Mobile Phone',
				'value' => '0400 111 211',
				'preferred' => true
			), array(
				'id' => '12345212',
				'type' => 'Work Phone',
				'value' => '4299 1212',
				'preferred' => false
			), array(
				'id' => '12345213',
				'type' => 'Work Email',
				'value' => 'person3_work@test.com',
				'preferred' => true
			), array(
				'id' => '12345214',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person3',
				'preferred' => false
			), array(
				'id' => '12345215',
				'type' => 'Linked-In',
				'value' => 'http://www.linkedin.com/in/person3',
				'preferred' => false
			), array(
				'id' => '12345216',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person3',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person3CommJSON ) );
		$person4CommJSON = $this->buildCommunicationsJSON( '1234522', array(
			array(
				'id' => '12345221',
				'type' => 'Work Phone',
				'value' => '4299 1221',
				'preferred' => true
			), array(
				'id' => '12345222',
				'type' => 'Email',
				'value' => 'person4@test.com',
				'preferred' => true
			), array(
				'id' => '12345123',
				'type' => 'Linked-In',
				'value' => 'https://www.linkedin.com/in/person4',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person4CommJSON ) );
		$person5CommJSON = $this->buildCommunicationsJSON( '1234531', array(
			array(
				'id' => '12345311',
				'type' => 'Home Phone',
				'value' => '4299 1311',
				'preferred' => false
			), array(
				'id' => '12345312',
				'type' => 'Mobile Phone',
				'value' => '0400 111 312',
				'preferred' => true
			), array(
				'id' => '12345313',
				'type' => 'Email',
				'value' => 'person5@test.com',
				'preferred' => true
			), array(
				'id' => '12345314',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person5',
				'preferred' => false
			), array(
				'id' => '12345315',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person5',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person5CommJSON ) );
		$person6CommJSON = $this->buildCommunicationsJSON( '1234532', array(
			array(
				'id' => '12345321',
				'type' => 'Home Phone',
				'value' => '(01) 4211 1321',
				'preferred' => true
			), array(
				'id' => '12345322',
				'type' => 'Mobile Phone',
				'value' => '0400 111 322',
				'preferred' => false
			), array(
				'id' => '12345323',
				'type' => 'Email',
				'value' => 'person6@test.com',
				'preferred' => false
			), array(
				'id' => '12345324',
				'type' => 'Home Email',
				'value' => 'person6@home.com',
				'preferred' => true
			), array(
				'id' => '12345325',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person6',
				'preferred' => false
			), array(
				'id' => '12345326',
				'type' => 'Linked-In',
				'value' => 'https://www.linkedin.com/in/person6',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person6CommJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonCommunications')
			->will($this->returnValueMap(array(
				array( 1234511, $person1CommJSON ), array( 1234512, $person2CommJSON ),
				array( 1234521, $person3CommJSON ), array( 1234522, $person4CommJSON ),
				array( 1234531, $person5CommJSON ), array( 1234532, $person6CommJSON ),
				array( '1234511', $person1CommJSON ), array( '1234512', $person2CommJSON ),
				array( '1234521', $person3CommJSON ), array( '1234522', $person4CommJSON ),
				array( '1234531', $person5CommJSON ), array( '1234532', $person6CommJSON ),
			)));

		// add attributes for position, with attribute name being 'Church Position'
		$person1AttrJSON = $this->buildPersonAttributesJSON( '1234511', array(
			array(
				'id' => '12345111',
				'group-id' => '112',
				'group-attr-id' => '1121',
				'group-name' => 'Not Church Position',
				'attr-name' => 'Some other attribute'
			), array(
				'id' => '12345112',
				'group-id' => '111',
				'group-attr-id' => '1111',
				'group-name' => 'Church Position',
				'attr-name' => 'Senior Minister'
			),
		));
		$this->assertNotNull( json_decode( $person1AttrJSON ) );
		$person2AttrJSON = $this->buildPersonAttributesJSON( '1234512', array(
			array(
				'id' => '12345121',
				'group-id' => '111',
				'group-attr-id' => '1112',
				'group-name' => 'Church Position',
				'attr-name' => 'Assistant Minister'
			)
		));
		$this->assertNotNull( json_decode( $person2AttrJSON ) );
		$person3AttrJSON = $this->buildPersonAttributesJSON( '1234521', array(
			array(
				'id' => '12345211',
				'group-id' => '111',
				'group-attr-id' => '1113',
				'group-name' => 'Church Position',
				'attr-name' => 'Worship Leader'
			)
		));
		$this->assertNotNull( json_decode( $person3AttrJSON ) );
		$person4AttrJSON = $this->buildPersonAttributesJSON( '1234522', array(
			array(
				'id' => '12345221',
				'group-id' => '111',
				'group-attr-id' => '1114',
				'group-name' => 'Church Position',
				'attr-name' => 'Vocalist'
			)
		));
		$this->assertNotNull( json_decode( $person4AttrJSON ) );
		$person5AttrJSON = $this->buildPersonAttributesJSON( '1234531', array(
			array(
				'id' => '12345311',
				'group-id' => '111',
				'group-attr-id' => '1115',
				'group-name' => 'Church Position',
				'attr-name' => 'Parish Councillor'
			), array(
				'id' => '12345321',
				'group-id' => '211',
				'group-attr-id' => '2111',
				'group-name' => 'Another Attribute Group',
				'attr-name' => 'Another Attribute'
			)
		));
		$this->assertNotNull( json_decode( $person5AttrJSON ) );
		$person6AttrJSON = $this->buildPersonAttributesJSON( '1234532', array(
			array(
				'id' => '12345321',
				'group-id' => '111',
				'group-attr-id' => '1115',
				'group-name' => 'Church Position',
				'attr-name' => 'Parish Councillor'
			)
		));
		$this->assertNotNull( json_decode( $person6AttrJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonAttributes')
			->will($this->returnValueMap(array(
				array( 1234511, $person1AttrJSON ), array( 1234512, $person2AttrJSON ),
				array( 1234521, $person3AttrJSON ), array( 1234522, $person4AttrJSON ),
				array( 1234531, $person5AttrJSON ), array( 1234532, $person6AttrJSON ),
				array( '1234511', $person1AttrJSON ), array( '1234512', $person2AttrJSON ),
				array( '1234521', $person3AttrJSON ), array( '1234522', $person4AttrJSON ),
				array( '1234531', $person5AttrJSON ), array( '1234532', $person6AttrJSON ),
			)));


		/*********************
		 *
		 *      Act
		 *
		 *********************/
		try {
			$this->sutF1PeopleDataProvider->setupForPeopleSync();
		} catch ( Exception $e ) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$x = 'stop';
			throw $e;
		}

		$groups = $this->sutF1PeopleDataProvider->getGroups();
		$people = $this->sutF1PeopleDataProvider->getPeople();

		// assert groups
		$this->assertInternalType( 'array', $groups );
		$this->assertCount( 3, $groups );
		// group 1
		$this->assertArrayHasKey( 1231, $groups );
		$group = $groups[ 1231 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1231, $group->id() );
		$this->assertEquals( 'Staff', $group->getName() );
		$this->assertEquals( 'All full-time staff', $group->getDescription() );
		// group 2
		$this->assertArrayHasKey( 1233, $groups );
		$group = $groups[ 1233 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1233, $group->id() );
		$this->assertEquals( 'Worship Team', $group->getName() );
		$this->assertEmpty( $group->getDescription() );
		// group 3
		$this->assertArrayHasKey( 1234, $groups );
		$group = $groups[ 1234 ];
		$this->assertInstanceOf( 'CTCI_PeopleGroupInterface', $group );
		$this->assertEquals( 1234, $group->id() );
		$this->assertEquals( 'Parish Council', $group->getName() );
		$this->assertEquals( 'Members of the parish council, who are responsible for administrative decisions.', $group->getDescription() );

		$this->assertInternalType( 'array', $people );
		$this->assertCount( 6, $people );
		$this->assertArrayHasKey( 1234511, $people );
		$this->assertArrayHasKey( 1234512, $people );
		$this->assertArrayHasKey( 1234521, $people );
		$this->assertArrayHasKey( 1234522, $people );
		$this->assertArrayHasKey( 1234531, $people );
		$this->assertArrayHasKey( 1234532, $people );

		// assert person 1
		$person1 = $people[1234511];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person1 );
		$this->assertEquals( 1234511, $person1->id() );
		$this->assertEquals( 'Dr Billy Graham', $person1->getName( 'T F L' ) );
		$this->assertEmpty( $person1->getPosition() );
		$this->assertFalse( $person1->syncPosition() );
		$this->assertEmpty( $person1->getPhone() );
		$this->assertFalse( $person1->syncPhone() );
		$this->assertEmpty( $person1->getEmail() );
		$this->assertFalse( $person1->syncEmail() );
		$this->assertEmpty( $person1->getURLs() );
		$this->assertFalse( $person1->syncFacebookURL() || $person1->syncTwitterURL() || $person1->syncLinkedInURL() );
		$this->assertEmpty( $person1->getGroups() );
		
		// assert person 2
		$person2 = $people[1234512];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person2 );
		$this->assertEquals( 1234512, $person2->id() );
		$this->assertEquals( 'Rick Warren', $person2->getName( 'F L' ) );
		$this->assertEmpty( $person2->getPosition() );
		$this->assertFalse( $person2->syncPosition() );
		$this->assertEmpty( $person2->getPhone() );
		$this->assertFalse( $person2->syncPhone() );
		$this->assertEmpty( $person2->getEmail() );
		$this->assertFalse( $person2->syncEmail() );
		$this->assertEmpty( $person2->getURLs() );
		$this->assertFalse( $person2->syncFacebookURL() || $person2->syncTwitterURL() || $person2->syncLinkedInURL() );
		$this->assertEmpty( $person2->getGroups() );

		// assert person 3
		$person3 = $people[1234521];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person3 );
		$this->assertEquals( 1234521, $person3->id() );
		$this->assertEquals( 'Jimmy Hand', $person3->getName( 'F L' ) );
		$this->assertEmpty( $person3->getPosition() );
		$this->assertFalse( $person3->syncPosition() );
		$this->assertEmpty( $person3->getPhone() );
		$this->assertFalse( $person3->syncPhone() );
		$this->assertEmpty( $person3->getEmail() );
		$this->assertFalse( $person3->syncEmail() );
		$this->assertEmpty( $person3->getURLs() );
		$this->assertFalse( $person3->syncFacebookURL() || $person3->syncTwitterURL() || $person3->syncLinkedInURL() );
		$this->assertEmpty( $person3->getGroups() );

		// assert person 4
		$person4 = $people[1234522];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person4 );
		$this->assertEquals( 1234522, $person4->id() );
		$this->assertEquals( 'Amy Grant', $person4->getName( 'F L' ) );
		$this->assertEmpty( $person4->getPosition() );
		$this->assertFalse( $person4->syncPosition() );
		$this->assertEmpty( $person4->getPhone() );
		$this->assertFalse( $person4->syncPhone() );
		$this->assertEmpty( $person4->getEmail() );
		$this->assertFalse( $person4->syncEmail() );
		$this->assertEmpty( $person4->getURLs() );
		$this->assertFalse( $person4->syncFacebookURL() || $person4->syncTwitterURL() || $person4->syncLinkedInURL() );
		$this->assertEmpty( $person4->getGroups() );

		// assert person 5
		$person5 = $people[1234531];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person5 );
		$this->assertEquals( 1234531, $person5->id() );
		$this->assertEquals( 'Mr Johnathan "John" Edward Doe II', $person5->getName( 'T FQ M L S' ) );
		$this->assertEmpty( $person5->getPosition() );
		$this->assertFalse( $person5->syncPosition() );
		$this->assertEmpty( $person5->getPhone() );
		$this->assertFalse( $person5->syncPhone() );
		$this->assertEmpty( $person5->getEmail() );
		$this->assertFalse( $person5->syncEmail() );
		$this->assertEmpty( $person5->getURLs() );
		$this->assertFalse( $person5->syncFacebookURL() || $person5->syncTwitterURL() || $person5->syncLinkedInURL() );
		$this->assertEmpty( $person5->getGroups() );

		// assert person 6
		$person6 = $people[1234532];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person6 );
		$this->assertEquals( 1234532, $person6->id() );
		$this->assertEquals( 'Mr Samuel "Sam" Joseph Steward', $person6->getName( 'T FQ M L' ) );
		$this->assertEmpty( $person6->getPosition() );
		$this->assertFalse( $person6->syncPosition() );
		$this->assertEmpty( $person6->getPhone() );
		$this->assertFalse( $person6->syncPhone() );
		$this->assertEmpty( $person6->getEmail() );
		$this->assertFalse( $person6->syncEmail() );
		$this->assertEmpty( $person6->getURLs() );
		$this->assertFalse( $person6->syncFacebookURL() || $person6->syncTwitterURL() || $person6->syncLinkedInURL() );
		$this->assertEmpty( $person6->getGroups() );

	}

	public function testSetupForPeopleSync_DeletingRemovedLinkedInURL() {
		/*
		 * Sync groups true
		 * 1 people list to sync, called Staff, with 3 people in it
		 * All sync options set to true
		 */
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPeopleGroups')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('getF1PeopleLists')
			->will($this->returnValue(array('Staff')));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPosition')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1PersonPositionAttribute')
			->will($this->returnValue('Church Position'));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonPhone')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonEmail')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonFacebookURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonTwitterURL')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPersonLinkedInURL')
			->will($this->returnValue(true));

		// build a list of 1 group
		$groupsJSON = $this->buildPeopleListsJSON( array(
			array(
				'id' => '1235',
				'name' => 'Staff',
				'description' => 'All full-time staff'
			)
		));
		// check that the test json data is well formed
		$this->assertNotNull( json_decode( $groupsJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleLists')
			->will($this->returnValue( $groupsJSON ));

		// these are the members of the Staff people list
		$memberListJSON = $this->buildMemberListJSON( array(
			'id' => '1235', 'name' => 'Staff'
		), array(
			array(
				'id' => '12345',
				'person-id' => '123456',
				'name' => 'John Doe'
			),
		));
		$this->assertNotNull( json_decode( $memberListJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPeopleListMembers')
			->will($this->returnValue($memberListJSON));

		// details of each member of the Staff group
		// the third person covers title, firstname, lastname, middlename, goesbyname and suffix
		$person1JSON = $this->buildPersonJSON( array(
			'id' => '123456',
			'title' => 'Mr',
			'firstName' => 'Johnathan',
			'lastName' => 'Doe',
			'middleName' => 'Edward',
			'goesByName' => 'John',
			'suffix' => 'II'
		));
		$this->assertNotNull( json_decode( $person1JSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPerson')
			->will($this->returnValueMap(array(
				array( 123456, $person1JSON ),
				array( '123456', $person1JSON ),
			)));

		// add communications data for each person
		// person 1: home, work (preferred), mobile phone. email, home email (preferred), work email, plus facebook, twitter and linkedin
		$person1CommJSON = $this->buildCommunicationsJSON( '123456', array(
			array(
				'id' => '1111',
				'type' => 'Home Phone',
				'value' => '4211 1113',
				'preferred' => false
			),
			array(
				'id' => '1113',
				'type' => 'Work Phone',
				'value' => '4299 1113',
				'preferred' => true
			),
			array(
				'id' => '1112',
				'type' => 'Mobile Phone',
				'value' => '0400 111 113',
				'preferred' => false
			),
			array(
				'id' => '1114',
				'type' => 'Email',
				'value' => 'person1@test.com',
				'preferred' => false
			),
			array(
				'id' => '1115',
				'type' => 'Home Email',
				'value' => 'person1_home@test.com',
				'preferred' => true
			),
			array(
				'id' => '1116',
				'type' => 'Work Email',
				'value' => 'person1_work@test.com',
				'preferred' => false
			),
			array(
				'id' => '1120',
				'type' => 'Facebook',
				'value' => 'https://www.facebook.com/person1',
				'preferred' => false
			),
			array(
				'id' => '1121',
				'type' => 'Linked-In',
				'value' => '',
				'preferred' => false
			),
			array(
				'id' => '1122',
				'type' => 'Twitter',
				'value' => 'https://twitter.com/person1',
				'preferred' => false
			)
		));
		$this->assertNotNull( json_decode( $person1CommJSON ) );
		$this->authClientMock
			->expects($this->any())
			->method('getPersonCommunications')
			->will($this->returnValueMap(array(
				array( 123456, $person1CommJSON ),
				array( '123456', $person1CommJSON ),
			)));

		// add attributes for position, with attribute name being 'Church Position'
		$person1AttrJSON = $this->buildPersonAttributesJSON( '123456', array(
			array(
				'id' => '111111',
				'group-id' => '111',
				'group-attr-id' => '1111',
				'group-name' => 'Not Church Position',
				'attr-name' => 'something completely other'
			), array(
				'id' => '111112',
				'group-id' => '112',
				'group-attr-id' => '1112',
				'group-name' => 'Not Church Position',
				'attr-name' => 'another other'
			), array(
				'id' => '111113',
				'group-id' => '113',
				'group-attr-id' => '1113',
				'group-name' => 'Church Position',
				'attr-name' => 'Pastor'
			), array(
				'id' => '111114',
				'group-id' => '114',
				'group-attr-id' => '1114',
				'group-name' => 'Abilities',
				'attr-name' => 'Presentation'
			)
		));
		$this->assertNotNull( json_decode( $person1AttrJSON ) );

		$this->authClientMock
			->expects($this->any())
			->method('getPersonAttributes')
			->will($this->returnValueMap(array(
				array( 123456, $person1AttrJSON ),
				array( '123456', $person1AttrJSON ),
			)));


		/*********************
		 *
		 *      Act
		 *
		 *********************/
		try {
			$this->sutF1PeopleDataProvider->setupForPeopleSync();
		} catch ( Exception $e ) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$x = 'stop';
			throw $e;
		}

		$people = $this->sutF1PeopleDataProvider->getPeople();

		$this->assertInternalType( 'array', $people );
		$this->assertCount( 1, $people );
		$this->assertArrayHasKey( 123456, $people );
		$person1 = $people[123456];
		$this->assertTrue( $person1->syncLinkedInURL() );
		$this->assertEquals( '', $person1->getLinkedInURL() );

	}

	protected function buildPeopleListsJSON( $lists ) {
		$json = '{"peopleLists":{"peopleList":[';
		foreach ( $lists as $list ) {
			$json .= sprintf('{"@id":"%1$s","@uri":"https://demo.staging.fellowshiponeapi.com/v1/people/lists/%1$s",', $list['id'] );
			$json .= sprintf('"name":"%s",', $list['name']);
			if ( isset( $list['description'] ) ) {
				$description = sprintf( '"%s"', $list['description'] );
			} else {
				$description = 'null';
			}
			$json .= sprintf('"description":%s,', $description);
			$json .= '"startDate":"2008-01-16T00:12:43",
				"isOpen":"true",
				"createdDate":"2005-04-15T10:47:56",
				"createdByPerson":{
				"@id":"5807",
					"@uri":"https://demo.staging.fellowshiponeapi.com/v1/people/5807"
				},
				"lastUpdatedDate":null,
				"lastUpdatedByPerson":{
				"@id":"",
				"@uri":""
			}';
			$json .= '},';
		}
		// remove last comma
		$json = substr( $json, 0, strlen( $json ) - 1 );
		$json .= ']}}';
		return $json;
	}

	protected function buildMemberListJSON( $list, $members ) {
		$json = '{"members":{"member":[';
		foreach ( $members as $member ) {
			$json .= sprintf('{"@id":"%2$s","@uri":"https://demo.staging.fellowshiponeapi.com/v1/people/lists/%1$s/members/%2$s",', $list['id'], $member['id'] );
			$json .= sprintf(
				'"peopleList":{
					"@id":"%1$s",
					"@uri":"https://demo.staging.fellowshiponeapi.com/v1/people/lists/%1$s",
					"name":"%2$s"
				},', $list['id'], $list['name'] );
			$json .= sprintf(
				'"person":{
					"@id":"%1$s",
					"@uri":"https://demo.staging.fellowshiponeapi.com/v1/people/%1$s",
					"name":"%2$s"
				},', $member['person-id'], $member['name'] );
			$json .= '"createdDate":"2012-06-27T11:49:55",
				"createdByPerson":{
					"@id":"1649898",
					"@uri":"https://demo.staging.fellowshiponeapi.com/v1/people/1649898"
				},
				"lastUpdatedDate":"2013-10-22T18:53:01",
				"lastUpdatedByPerson":{
					"@id":"",
					"@uri":""
				}';
			$json .= '},';
		}
		// remove last comma
		$json = substr( $json, 0, strlen( $json ) - 1 );
		$json .= ']}}';
		return $json;
	}

	protected function buildPersonJSON( $person ) {
		$json = '{"person":{';
		$json .= sprintf('"@id":"%s",', $person['id']);
		$json .= sprintf('"@uri":"https://demo.fellowshiponeapi.com/v1/People/%s",', $person['id']);
		$json .= '"@imageURI": "",
        "@oldID": "",
        "@iCode": "rD94LTAKe4ROXa50SLH7oQ==",
        "@householdID": "14338522",
        "@oldHouseholdID": "",';
		$json .= sprintf('"title":%s,', isset( $person['title'] ) ? '"' . $person['title'] . '"' : 'null' );
		$json .= sprintf('"salutation":%s,', isset( $person['salutation'] ) ? '"' . $person['salutation'] . '"' : 'null' );
		$json .= sprintf('"prefix":%s,', isset( $person['prefix'] ) ? '"' . $person['prefix'] . '"' : 'null' );
		$json .= sprintf('"firstName":%s,', isset( $person['firstName'] ) ? '"' . $person['firstName'] . '"' : 'null' );
		$json .= sprintf('"lastName":%s,', isset( $person['lastName'] ) ? '"' . $person['lastName'] . '"' : 'null' );
		$json .= sprintf('"suffix":%s,', isset( $person['suffix'] ) ? '"' . $person['suffix'] . '"' : 'null' );
		$json .= sprintf('"middleName":%s,', isset( $person['middleName'] ) ? '"' . $person['middleName'] . '"' : 'null' );
		$json .= sprintf('"goesByName":%s,', isset( $person['goesByName'] ) ? '"' . $person['goesByName'] . '"' : 'null' );
		$json .= '"formerName": null,
        "gender": "Male",
        "dateOfBirth": "1976-08-13T00:00:00",
        "maritalStatus": "Married",
        "householdMemberType": {
            "@id": "1",
            "@uri": "https://demo.fellowshiponeapi.com/v1/People/HouseholdMemberTypes/1",
            "name": "Head"
        },
        "isAuthorized": "true",
        "status": {
            "@id": "1",
            "@uri": "https://demo.fellowshiponeapi.com/v1/People/Statuses/1",
            "name": "Member",
            "comment": null,
            "date": null,
            "subStatus": {
                "@id": "",
                "@uri": "",
                "name": null
            }
        },
        "occupation": {
            "@id": "",
            "@uri": "",
            "name": null,
            "description": null
        },
        "employer": "Fellowship Technologies",
        "school": {
            "@id": "",
            "@uri": "",
            "name": null
        },
        "denomination": {
            "@id": "",
            "@uri": "",
            "name": null
        },
        "formerChurch": null,
        "barCode": "dfhg",
        "memberEnvelopeCode": "12341234",
        "defaultTagComment": null,
        "weblink": {
            "userID": null,
            "passwordHint": null,
            "passwordAnswer": null
        },
        "solicit": null,
        "thank": null,
        "firstRecord": "2008-12-11T07:41:07",
        "lastMatchDate": null,
        "createdDate": "2008-12-11T07:41:08",
        "lastUpdatedDate": "2009-04-30T15:12:49"';
		$json .= '}}';
		return $json;
	}

	protected function buildCommunicationsJSON( $personID, $communications ) {
		$json = '{"communications":{"communication":[';
		foreach ( $communications as $comm ) {
			$json .= '{"@array": "true",';
			$json .= sprintf( '"@id":"%s",', $comm['id'] );
			$json .= sprintf( '"@uri":"https://demo.fellowshiponeapi.com/v1/Communications/%s",', $comm['id'] );
			$json .= '"household": {
                    "@id": "1552366",
                    "@uri": "https://demo.fellowshiponeapi.com/v1/Households/1552366"
                },';
			$json .= sprintf( '"person": {
                    "@id": "%1$s",
                    "@uri": "https://demo.fellowshiponeapi.com/v1/People/%1$s"
                },', $personID );
			switch ( $comm['type'] ) {
				case 'Home Phone':
					$json .= '"communicationType": {
	                    "@id": "1",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/1",
	                    "name": "Home Phone"
	                },
	                "communicationGeneralType": "Telephone",';
					break;
				case 'Work Phone':
					$json .= '"communicationType": {
	                    "@id": "2",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/2",
	                    "name": "Work Phone"
	                },
	                "communicationGeneralType": "Telephone",';
					break;
				case 'Mobile Phone':
					$json .= '"communicationType": {
	                    "@id": "3",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/3",
	                    "name": "Mobile Phone"
	                },
	                "communicationGeneralType": "Telephone",';
					break;
				case 'Email':
					$json .= '"communicationType": {
	                    "@id": "4",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/4",
	                    "name": "Email"
	                },
	                "communicationGeneralType": "Email",';
					break;
				case 'Home Email':
					$json .= '"communicationType": {
	                    "@id": "5",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/5",
	                    "name": "Home Email"
	                },
	                "communicationGeneralType": "Email",';
					break;
				case 'InFellowship Login':
					$json .= '"communicationType": {
	                    "@id": "6",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/6",
	                    "name": "InFellowship Login"
	                },
	                "communicationGeneralType": "Email",';
					break;
				case 'Web':
					$json .= '"communicationType": {
	                    "@id": "102",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/102",
	                    "name": "Web Address"
	                },
	                "communicationGeneralType": "Web",';
					break;
				case 'Work Email':
					$json .= '"communicationType": {
	                    "@id": "127",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/127",
	                    "name": "Work Email"
	                },
	                "communicationGeneralType": "Email",';
					break;
				case 'Emergency Phone':
					$json .= '"communicationType": {
	                    "@id": "138",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/138",
	                    "name": "Emergency Phone"
	                },
	                "communicationGeneralType": "Telephone",';
					break;
				case 'Facebook':
					$json .= '"communicationType": {
	                    "@id": "201",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/201",
	                    "name": "Facebook"
	                },
	                "communicationGeneralType": "SocialMedia",';
					break;
				case 'Linked-In':
					$json .= '"communicationType": {
	                    "@id": "202",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/202",
	                    "name": "Linked-In"
	                },
	                "communicationGeneralType": "SocialMedia",';
					break;
				case 'Twitter':
					$json .= '"communicationType": {
	                    "@id": "203",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/203",
	                    "name": "Twitter"
	                },
	                "communicationGeneralType": "SocialMedia",';
					break;
			}
			$json .= sprintf( '"communicationValue":"%s",', $comm['value'] );
			$json .= sprintf( '"searchCommunicationValue":"%s",', $comm['value'] );
			if ( $comm['preferred'] ) {
				$json .= '"preferred":"true",';
			} else {
				$json .= '"preferred":"false",';
			}
			$json .= '"communicationComment": null,
                "createdDate": null,
                "lastUpdatedDate": "2004-03-14T16:40:40"';
			$json .= '},';
		}
		// remove last comma
		$json = substr( $json, 0, strlen( $json ) - 1 );
		$json .= ']}}';
		return $json;
	}

	protected function buildPersonAttributesJSON( $personID, $attributes ) {
		$json = '{"attributes":{"attribute":[';

		foreach ( $attributes as $attribute ) {
			$json .= '{"@array":"true",';
			$json .= sprintf( '"@id":"%s",', $attribute['id'] );
			$json .= sprintf( '"@id":"https://demo.fellowshiponeapi.com/v1/People/%s/Attributes/%s",', $personID, $attribute['id'] );
			$json .= sprintf( '"person": {
                    "@id": "%1$s",
                    "@uri": "https://demo.fellowshiponeapi.com/v1/People/%1$s"
                },
                ', $personID);
			$json .= sprintf( '"attributeGroup": {
                    "@id": "%1$s",
                    "@uri": "https://demo.fellowshiponeapi.com/v1/People/AttributeGroups/%1$s",
                    "name": "%2$s",
                    "attribute": {
                        "@id": "%3$s",
                        "@uri": "https://demo.fellowshiponeapi.com/v1/People/AttributeGroups/%1$s/Attributes/%3$s",
                        "name": "%4$s"
                    }
                },', $attribute['group-id'], $attribute['group-name'], $attribute['group-attr-id'], $attribute['attr-name'] );
			$json .= '"startDate": null,
                "endDate": null,
                "comment": null,
                "createdDate": "2009-03-20T08:14:23",
                "lastUpdatedDate": "2009-03-24T11:12:17"';
			$json .= '},';
		}
		// remove last comma
		$json = substr( $json, 0, strlen( $json ) - 1 );
		$json .= ']}}';
		return $json;
	}
}
 