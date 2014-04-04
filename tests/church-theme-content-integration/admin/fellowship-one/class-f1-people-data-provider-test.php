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
require_once dirname( __FILE__ ) . '/../../../../church-theme-content-integration/admin/class-logger.php';

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
				'type' => 'Linkedin',
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
				case 'Linkedin':
					$json .= '"communicationType": {
	                    "@id": "202",
	                    "@uri": "https://demo.fellowshiponeapi.com/v1/Communications/CommunicationTypes/202",
	                    "name": "Linkedin"
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
 