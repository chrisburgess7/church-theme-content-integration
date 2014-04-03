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
		// todo: add communications
		/*
		 * Sync groups true
		 * 1 people list to sync, called Staff
		 */
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('f1SyncPeopleGroups')
			->will($this->returnValue(true));
		$this->peopleSyncSettingsMock
			->expects($this->any())
			->method('getF1PeopleLists')
			->will($this->returnValue(array('Staff')));
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

		try {
			$this->sutF1PeopleDataProvider->setupForPeopleSync();
		} catch ( Exception $e ) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			$x = 'stop';
			throw $e;
		}

		$groups = $this->sutF1PeopleDataProvider->getGroups();
		$people = $this->sutF1PeopleDataProvider->getPeople();

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
		$person1 = $people[123456];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person1 );
		$this->assertEquals( 123456, $person1->id() );
		$this->assertEquals( 'Dr Steve Jobs', $person1->getName( 'T F L' ) );
		$person1Groups = $person1->getGroups();
		$this->assertCount( 1, $person1Groups );
		$firstGroup = reset( $person1Groups );
		$this->assertEquals( 1235, $firstGroup->id() );

		$person2 = $people[123457];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person2 );
		$this->assertEquals( 123457, $person2->id() );
		$this->assertEquals( 'Max Once', $person2->getName( 'F L' ) );
		$person2Groups = $person2->getGroups();
		$this->assertCount( 1, $person2Groups );
		$firstGroup = reset( $person2Groups );
		$this->assertEquals( 1235, $firstGroup->id() );

		$person3 = $people[123458];
		$this->assertInstanceOf( 'CTCI_PersonInterface', $person3 );
		$this->assertEquals( 123458, $person3->id() );
		$this->assertEquals( 'Mr Johnathan "John" Edward Doe II', $person3->getName( 'T FQ M L S' ) );
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
}
 