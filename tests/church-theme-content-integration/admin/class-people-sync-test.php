<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 8/04/14
 * Time: 2:32 PM
 */

require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-people-sync.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/interface-data-provider.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/interface-people-data-provider.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/interface-status-tracker.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/fellowship-one/fellowship-one.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/fellowship-one/class-f1-people-data-provider.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-logger.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-status-tracker.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-wpal.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-person.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-people-group.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-ctc-group.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-ctc-person.php';

class CTCI_PeopleSyncTest extends PHPUnit_Framework_TestCase {

	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $dataProviderMock;
	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $peopleDataProviderMock;
	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $statusTrackerMock;
	/** @var PHPUnit_Framework_MockObject_MockObject */
	protected $wpalMock;

	/** @var CTCI_PeopleSync */
	protected $sutPeopleSync;

	public function setUp() {
		$this->dataProviderMock = $this->getMockBuilder('CTCI_Fellowship_One')
			->disableOriginalConstructor()
			->getMock();

		$this->peopleDataProviderMock = $this->getMockBuilder('CTCI_F1PeopleDataProvider')
			->disableOriginalConstructor()
			->getMock();

		$this->dataProviderMock
			->expects($this->any())
			->method('isDataProviderFor')
			->with( CTCI_PeopleSync::getTag() )
			->will( $this->returnValue( true ) );

		$this->dataProviderMock
			->expects($this->any())
			->method('getDataProviderFor')
			->with( CTCI_PeopleSync::getTag() )
			->will( $this->returnValue( $this->peopleDataProviderMock ) );

		// we'll test as f1
		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('getProviderPersonTag')
			->will( $this->returnValue('f1') );

		$this->statusTrackerMock = $this->getMockBuilder('CTCI_StatusTracker')
			->disableOriginalConstructor()
			->getMock();

		$this->wpalMock = $this->getMockBuilder('CTCI_WPAL')
			->disableOriginalConstructor()
			->getMock();

		$this->sutPeopleSync = new CTCI_PeopleSync( $this->wpalMock, $this->statusTrackerMock );
		$this->sutPeopleSync->setDataProvider( $this->dataProviderMock );

		// set up expectations for all calls to run()
		$this->peopleDataProviderMock
			->expects($this->once())
			->method('setupForPeopleSync');

		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('cleanUpAfterPeopleSync');
	}

	public function testRun_4NewPeople_2NewGroups_SyncGroupsTrue() {
		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('syncGroups')
			->will( $this->returnValue( true ) );

		/**
		 * updateGroups
		 */

		// new groups
		$group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
		$group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );
		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getGroups')
			->will( $this->returnValue( array(
				1 => $group1,
				2 => $group2,
			)));

		// no existing CTC groups attached to the service provider
		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCGroupsAttachedViaProvider')
			->with( 'f1' )
			->will( $this->returnValue( array() ) );

		$this->wpalMock
			->expects( $this->never() )
			->method('deleteCTCGroup');
		$this->wpalMock
			->expects( $this->never() )
			->method('unattachCTCGroup');

		// 1 existing unattached group to match
		$unattachedCTCGroup = new CTCI_CTCGroup( 21, 'Group 1', 'desc' );
		$call = 1;
		$this->wpalMock
			->expects( $this->exactly(2) )
			->method('getUnattachedCTCGroups')
			->will( $this->returnCallback( function() use ( $call, $unattachedCTCGroup ) {
				if ( $call === 1 ) {
					return array( $unattachedCTCGroup );
				} else {
					return array();
				}
			} ) );

		$this->wpalMock
			->expects( $this->once() )
			->method('attachCTCGroup')
			->with( $unattachedCTCGroup, $group1 );
		$this->wpalMock
			->expects( $this->once() )
			->method('updateCTCGroup')
			->with( $unattachedCTCGroup, $group1 );

		$this->wpalMock
			->expects( $this->once() )
			->method('createAttachedCTCGroup')
			->with( $group2 );

		/**
		 * Update people section
		 */

		/** @var CTCI_PersonInterface[] $people */
		$people = array(
			1 => new CTCI_Person( 'f1', 1 ),
			2 => new CTCI_Person( 'f1', 2 ),
			3 => new CTCI_Person( 'f1', 3 ),
			4 => new CTCI_Person( 'f1', 4 )
		);
		$ctcPeople = array();
		foreach ( $people as $person ) {
			$person
				->setNameFormat('F L')
				->setFirstName( 'Person' )
				->setLastName( $person->id() )
				->setEmail( 'person' . $person->id() . '@test.com' );

			$ctcPerson = new CTCI_CTCPerson();
			$ctcPerson->setId( $person->id() );
			$ctcPerson->setName( $person->getName() );
			$ctcPerson->setEmail( $person->getEmail() );
			$ctcPeople[ $person->id() ] = $ctcPerson;
		}
		// and add each person to a group
		$people['1']->addGroup( $group1 );
		$people['2']->addGroup( $group2 );
		$people['3']->addGroup( $group1 );
		$people['4']->addGroup( $group2 );

		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getPeople')
			->will( $this->returnValue( $people ) );

		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCPeopleAttachedViaProvider')
			->with( $this->equalTo('f1') )
			->will( $this->returnValue( array() ) );

		$this->wpalMock
			->expects( $this->never() )
			->method( 'unattachCTCPerson' );
		$this->wpalMock
			->expects( $this->never() )
			->method( 'deleteCTCPerson' );
		$this->wpalMock
			->expects( $this->never() )
			->method( 'unpublishCTCPerson' );

		// person 2 we'll setup to be attached to existing
		$ctcPersonUnrelated = new CTCI_CTCPerson();
		$ctcPersonUnrelated->setName( 'Unrelated Person' );
		$ctcPerson2 = $ctcPeople[2];
		$call_getUnattachedCTCPeople = 1;
		$this->wpalMock
			->expects( $this->exactly(4) )
			->method('getUnattachedCTCPeople')
			->will( $this->returnCallback( function() use ( $call_getUnattachedCTCPeople, $ctcPerson2, $ctcPersonUnrelated ) {
				if ( $call_getUnattachedCTCPeople <= 2 ) {
					// return ctc person 2 as existing unattached
					return array( $ctcPerson2, $ctcPersonUnrelated );
				} else {
					// on the remaining calls, ctc person 2 has been attached, so should no longer be returned as unattached
					return array( $ctcPersonUnrelated );
				}
			}));

		// set up the one call to attach for person 2
		$this->wpalMock
			->expects( $this->once() )
			->method( 'attachCTCPerson' )
			->with( $this->equalTo( $ctcPeople[2] ), $this->equalTo( $people['2'] ) )
			->will( $this->returnValue( true ) );

		/* protected function syncCTCPerson */

		// the calls for person 2 who is attached to existing CTC person
		$this->wpalMock
			->expects( $this->once() )
			->method('updateCTCPerson')
			->with( $this->equalTo( $ctcPerson2 ) );

		/* protected createNewCTCPerson */

		// check that createAttachedCTCPerson called on both person 1 and 3
		$expArguments_createAttachedCTCPerson = array( $people['1'], $people['3'], $people['4'] );
		$ctcPerson1 = $ctcPeople[1];
		$ctcPerson3 = $ctcPeople[3];
		$ctcPerson4 = $ctcPeople[4];
		$badArg_createAttachedCTCPerson = false;
		$this->wpalMock
			->expects( $this->exactly(3) )
			->method('createAttachedCTCPerson')
			->with( $this->logicalOr(
				$this->equalTo( $people['1'] ),
				$this->equalTo( $people['3'] ),
				$this->equalTo( $people['4'] )
			))->will( $this->returnCallback(
				function( CTCI_PersonInterface $param ) use (
					&$expArguments_createAttachedCTCPerson,
					$ctcPerson1,
					$ctcPerson3,
					$ctcPerson4,
					&$badArg_createAttachedCTCPerson
				) {
					if(($key = array_search($param, $expArguments_createAttachedCTCPerson)) !== false) {
						unset($expArguments_createAttachedCTCPerson[$key]); // remove the param to test that all arguments are called with
					}
					// the method creates a ctc person object and returns it
					if ( $param->id() === 1 ) {
						return $ctcPerson1;
					} elseif ( $param->id() === 3 ) {
						return $ctcPerson3;
					} elseif ( $param->id() === 4 ) {
						return $ctcPerson4;
					} else {
						$badArg_createAttachedCTCPerson = true;
						return null;
					}
				}
			));

		// check setCTCPersonsGroups called on each
		// for person 2, called in syncCTCPerson during attach
		// for person 1 and 3, in createNewCTCPerson
		$expArguments_setCTCPersonsGroups = $ctcPeople;
		$argumentMismatch_setCTCPersonsGroups = false;
		$this->wpalMock
			->expects( $this->exactly(4) )
			->method('setCTCPersonsGroups')
			->with( $this->logicalOr(
				$this->equalTo( $ctcPeople[1] ),
				$this->equalTo( $ctcPeople[2] ),
				$this->equalTo( $ctcPeople[3] ),
				$this->equalTo( $ctcPeople[4] )
			), $this->logicalOr(
				$this->equalTo( $people['1']->getGroups() ),
				$this->equalTo( $people['2']->getGroups() ),
				$this->equalTo( $people['3']->getGroups() ),
				$this->equalTo( $people['4']->getGroups() )
			) )->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param1, $param2 )
					use ( &$expArguments_setCTCPersonsGroups, &$argumentMismatch_setCTCPersonsGroups, $people ) {
						if(($key = array_search($param1, $expArguments_setCTCPersonsGroups)) !== false) {
							// remove the param to test that all arguments are called with
							unset($expArguments_setCTCPersonsGroups[$key]);
							// the second param should be the same as the groups returned from the person
							// with the same id
							if ( $param2 !== $people[ $param1->id() ]->getGroups() ) {
								$argumentMismatch_setCTCPersonsGroups = true;
							}
						}
						return null;
				}
			));

		/**
		 * Act!
		 */
		$this->sutPeopleSync->run();

		// assert expected argument arrays are empty
		$this->assertFalse( $badArg_createAttachedCTCPerson, 'createAttachedCTCPerson called with bad argument' );
		$this->assertEmpty( $expArguments_createAttachedCTCPerson );
		$this->assertEmpty( $expArguments_setCTCPersonsGroups );
		$this->assertFalse( $argumentMismatch_setCTCPersonsGroups );

	}

    public function testRun_4NewPeople_2NewGroups_SyncGroupsFalse() {
        $this->peopleDataProviderMock
            ->expects( $this->any() )
            ->method('syncGroups')
            ->will( $this->returnValue( false ) );

        /**
         * updateGroups
         */

        // new groups
        $group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
        $group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );

        $this->peopleDataProviderMock
            ->expects( $this->never() )
            ->method('getGroups');

        // no existing CTC groups attached to the service provider
        $this->wpalMock
            ->expects( $this->never() )
            ->method('getCTCGroupsAttachedViaProvider');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('deleteCTCGroup');
        $this->wpalMock
            ->expects( $this->never() )
            ->method('unattachCTCGroup');

        // 1 existing unattached group to match
        $this->wpalMock
            ->expects( $this->never() )
            ->method('getUnattachedCTCGroups');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('attachCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('updateCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('createAttachedCTCGroup');

        /**
         * Update people section
         */

        /** @var CTCI_PersonInterface[] $people */
        $people = array(
            1 => new CTCI_Person( 'f1', 1 ),
            2 => new CTCI_Person( 'f1', 2 ),
            3 => new CTCI_Person( 'f1', 3 ),
            4 => new CTCI_Person( 'f1', 4 )
        );
        $ctcPeople = array();
        foreach ( $people as $person ) {
            $person
                ->setNameFormat('F L')
                ->setFirstName( 'Person' )
                ->setLastName( $person->id() )
                ->setEmail( 'person' . $person->id() . '@test.com' );

            $ctcPerson = new CTCI_CTCPerson();
            $ctcPerson->setId( $person->id() );
            $ctcPerson->setName( $person->getName() );
            $ctcPerson->setEmail( $person->getEmail() );
            $ctcPeople[ $person->id() ] = $ctcPerson;
        }
        // and add each person to a group
        $people['1']->addGroup( $group1 );
        $people['2']->addGroup( $group2 );
        $people['3']->addGroup( $group1 );
        $people['4']->addGroup( $group2 );

        $this->peopleDataProviderMock
            ->expects( $this->once() )
            ->method('getPeople')
            ->will( $this->returnValue( $people ) );

        $this->wpalMock
            ->expects( $this->once() )
            ->method('getCTCPeopleAttachedViaProvider')
            ->with( $this->equalTo('f1') )
            ->will( $this->returnValue( array() ) );

        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'unattachCTCPerson' );
        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'deleteCTCPerson' );
        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'unpublishCTCPerson' );

        // person 2 we'll setup to be attached to existing
        $ctcPersonUnrelated = new CTCI_CTCPerson();
        $ctcPersonUnrelated->setName( 'Unrelated Person' );
        $ctcPerson2 = $ctcPeople[2];
        $call_getUnattachedCTCPeople = 1;
        $this->wpalMock
            ->expects( $this->exactly(4) )
            ->method('getUnattachedCTCPeople')
            ->will( $this->returnCallback( function() use ( $call_getUnattachedCTCPeople, $ctcPerson2, $ctcPersonUnrelated ) {
                if ( $call_getUnattachedCTCPeople <= 2 ) {
                    // return ctc person 2 as existing unattached
                    return array( $ctcPerson2, $ctcPersonUnrelated );
                } else {
                    // on the remaining calls, ctc person 2 has been attached, so should no longer be returned as unattached
                    return array( $ctcPersonUnrelated );
                }
            }));

        // set up the one call to attach for person 2
        $this->wpalMock
            ->expects( $this->once() )
            ->method( 'attachCTCPerson' )
            ->with( $this->equalTo( $ctcPeople[2] ), $this->equalTo( $people['2'] ) )
            ->will( $this->returnValue( true ) );

        /* protected function syncCTCPerson */

        // the calls for person 2 who is attached to existing CTC person
        $this->wpalMock
            ->expects( $this->once() )
            ->method('updateCTCPerson')
            ->with( $this->equalTo( $ctcPerson2 ) );

        /* protected createNewCTCPerson */

        // check that createAttachedCTCPerson called on both person 1 and 3
        $expArguments_createAttachedCTCPerson = array( $people['1'], $people['3'], $people['4'] );
        $ctcPerson1 = $ctcPeople[1];
        $ctcPerson3 = $ctcPeople[3];
        $ctcPerson4 = $ctcPeople[4];
        $badArg_createAttachedCTCPerson = false;
        $this->wpalMock
            ->expects( $this->exactly(3) )
            ->method('createAttachedCTCPerson')
            ->with( $this->logicalOr(
                $this->equalTo( $people['1'] ),
                $this->equalTo( $people['3'] ),
                $this->equalTo( $people['4'] )
            ))->will( $this->returnCallback(
                function( CTCI_PersonInterface $param ) use (
                    &$expArguments_createAttachedCTCPerson,
                    $ctcPerson1,
                    $ctcPerson3,
                    $ctcPerson4,
                    &$badArg_createAttachedCTCPerson
                ) {
                    if(($key = array_search($param, $expArguments_createAttachedCTCPerson)) !== false) {
                        unset($expArguments_createAttachedCTCPerson[$key]); // remove the param to test that all arguments are called with
                    }
                    // the method creates a ctc person object and returns it
                    if ( $param->id() === 1 ) {
                        return $ctcPerson1;
                    } elseif ( $param->id() === 3 ) {
                        return $ctcPerson3;
                    } elseif ( $param->id() === 4 ) {
                        return $ctcPerson4;
                    } else {
                        $badArg_createAttachedCTCPerson = true;
                        return null;
                    }
                }
            ));

        $this->wpalMock
            ->expects( $this->never() )
            ->method('setCTCPersonsGroups');

        /**
         * Act!
         */
        $this->sutPeopleSync->run();

        // assert expected argument arrays are empty
        $this->assertFalse( $badArg_createAttachedCTCPerson, 'createAttachedCTCPerson called with bad argument' );
        $this->assertEmpty( $expArguments_createAttachedCTCPerson );

    }

	public function testRun_3ExistingPeople1New_2ExistingGroups_SyncGroupsTrue() {
		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('syncGroups')
			->will( $this->returnValue( true ) );

		/**
		 * updateGroups
		 */

		// new groups
		$group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
		$group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );
		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getGroups')
			->will( $this->returnValue( array(
				1 => $group1,
				2 => $group2,
			)));

		$ctcGroup1 = new CTCI_CTCGroup( 11, 'Group 1', 'desc' );
		$ctcGroup1->setAttachedGroup( 'f1', 1 );
		$ctcGroup2 = new CTCI_CTCGroup( 12, 'Group 2', 'desc' );
		$ctcGroup2->setAttachedGroup( 'f1', 2 );

		// both groups exist and are attached to the service provider
		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCGroupsAttachedViaProvider')
			->with( 'f1' )
			->will( $this->returnValue( array( $ctcGroup1, $ctcGroup2 ) ) );

		// check the arguments called on updateCTCGroup
		$expectedArguments_updateCTCGroup = array( $ctcGroup1, $ctcGroup2 );
		$this->wpalMock
			->expects( $this->exactly(2) )
			->method('updateCTCGroup')
			->with( $this->logicalOr( $ctcGroup1, $ctcGroup2 ), $this->logicalOr( $group1, $group2 ) )
			->will( $this->returnCallback(
				function( CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group ) use (
					&$expectedArguments_updateCTCGroup
				) {
					if(($key = array_search($ctcGroup, $expectedArguments_updateCTCGroup)) !== false) {
						// remove the param to test that all arguments are called with
						unset($expectedArguments_updateCTCGroup[$key]);
					}
					if ( ( $ctcGroup->id() !== 11 && $ctcGroup->id() !== 12 ) ||
						( $ctcGroup->id() === 11 && $group->id() !== 1 ) ||
						( $ctcGroup->id() === 12 && $group->id() !== 2 ) ) {
						throw new Exception( 'updateCTCGroup called with incorrect arguments' );
					}
				}
			));

		$this->wpalMock
			->expects( $this->never() )
			->method('deleteCTCGroup');
		$this->wpalMock
			->expects( $this->never() )
			->method('unattachCTCGroup');

		$this->wpalMock
			->expects( $this->never() )
			->method('getUnattachedCTCGroups');

		$this->wpalMock
			->expects( $this->never() )
			->method('attachCTCGroup');

		$this->wpalMock
			->expects( $this->never() )
			->method('createAttachedCTCGroup');

		/**
		 * Update people section
		 */

		/** @var CTCI_PersonInterface[] $people */
		$people = array(
			'1' => new CTCI_Person( 'f1', 1 ),
			'2' => new CTCI_Person( 'f1', 2 ),
			'3' => new CTCI_Person( 'f1', 3 ),
			'4' => new CTCI_Person( 'f1', 4 ) // we'll call this the new person
		);
		$ctcPeople = array();
		foreach ( $people as $person ) {
			$person
				->setNameFormat('F L')
				->setFirstName( 'Person' )
				->setLastName( $person->id() )
				->setEmail( 'person' . $person->id() . '@test.com' );

			$ctcPerson = new CTCI_CTCPerson();
			$ctcPerson->setId( $person->id() );
			$ctcPerson->setName( $person->getName() );
			$ctcPerson->setEmail( $person->getEmail() );
			$ctcPeople[ $person->id() ] = $ctcPerson;
		}
		// and add each person to a group
		$people['1']->addGroup( $group1 );
		$people['2']->addGroup( $group2 );
		$people['3']->addGroup( $group2 );
		$people['4']->addGroup( $group1 );

		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getPeople')
			->will( $this->returnValue( $people ) );

		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCPeopleAttachedViaProvider')
			->with( $this->equalTo('f1') )
			->will( $this->returnValue( array( $ctcPeople['1'], $ctcPeople['2'], $ctcPeople['3'] ) ) );

		$this->wpalMock
			->expects( $this->exactly(3) )
			->method('getAttachedPersonId')
			->will( $this->returnValueMap( array(
				array( $ctcPeople[1], '1' ),
				array( $ctcPeople[2], '2' ),
				array( $ctcPeople[3], '3' ),
			)));

		// check setCTCPersonsGroups called on ctc people 1, 2, 3
		// called in syncCTCPerson
		$expArguments_setCTCPersonsGroups = $ctcPeople;
		$argumentMismatch_setCTCPersonsGroups = false;
		$this->wpalMock
			->expects( $this->exactly(4) )
			->method('setCTCPersonsGroups')
			->with( $this->logicalOr(
				$this->equalTo( $ctcPeople[1] ),
				$this->equalTo( $ctcPeople[2] ),
				$this->equalTo( $ctcPeople[3] ),
				$this->equalTo( $ctcPeople[4] )
			), $this->logicalOr(
				$this->equalTo( $people['1']->getGroups() ),
				$this->equalTo( $people['2']->getGroups() ),
				$this->equalTo( $people['3']->getGroups() ),
				$this->equalTo( $people['4']->getGroups() )
			) )->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param1, $param2 ) use (
					&$expArguments_setCTCPersonsGroups, &$argumentMismatch_setCTCPersonsGroups, $people
				) {
					if(($key = array_search($param1, $expArguments_setCTCPersonsGroups)) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_setCTCPersonsGroups[$key]);
						// the second param should be the same as the groups returned from the person
						// with the same id
						if ( $param2 !== $people[ $param1->id() ]->getGroups() ) {
							$argumentMismatch_setCTCPersonsGroups = true;
						}
					}
					return null;
				}
			));

		// update person called on ctc people 1, 2 and 3
		$expArguments_updateCTCPerson = array( $ctcPeople[1], $ctcPeople[2], $ctcPeople[3] );
		$this->wpalMock
			->expects( $this->exactly(3) )
			->method('updateCTCPerson')
			->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_updateCTCPerson ) {
					if(($key = array_search($param, $expArguments_updateCTCPerson)) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_updateCTCPerson[$key]);
					}
					return null;
				}
			));

		// no previously attached people to deal with here...
		$this->wpalMock
			->expects( $this->never() )
			->method( 'unattachCTCPerson' );
		$this->wpalMock
			->expects( $this->never() )
			->method( 'deleteCTCPerson' );
		$this->wpalMock
			->expects( $this->never() )
			->method( 'unpublishCTCPerson' );


		// person 4 is new - not be attached to an existing person
		$this->wpalMock
			->expects( $this->once() )
			->method('getUnattachedCTCPeople')
			->will( $this->returnValue( array() ) );

		$this->wpalMock
			->expects( $this->never() )
			->method( 'attachCTCPerson' );

		/* protected function createNewCTCPerson */

		// for person 4 who is new
		$this->wpalMock
			->expects( $this->once() )
			->method('createAttachedCTCPerson')
			->with( $this->equalTo( $people[4] ) )
			->will( $this->returnValue( $ctcPeople[4] ) );

		// the call to setCTCPersonsGroups is defined above amongst the other ones

		/**
		 * Act!
		 */
		$this->sutPeopleSync->run();

		// assert expected argument arrays are empty
        $this->assertEmpty( $expArguments_updateCTCPerson );
        $this->assertEmpty( $expectedArguments_updateCTCGroup );
		$this->assertEmpty( $expArguments_setCTCPersonsGroups );
		$this->assertFalse( $argumentMismatch_setCTCPersonsGroups );

	}

    public function testRun_3ExistingPeople1New_2ExistingGroups_SyncGroupsFalse() {
        $this->peopleDataProviderMock
            ->expects( $this->any() )
            ->method('syncGroups')
            ->will( $this->returnValue( false ) );

        /**
         * updateGroups
         */

        // new groups
        $group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
        $group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );
        $this->peopleDataProviderMock
            ->expects( $this->never() )
            ->method('getGroups');

        // both groups exist and are attached to the service provider
        $this->wpalMock
            ->expects( $this->never() )
            ->method('getCTCGroupsAttachedViaProvider');

        // check the arguments called on updateCTCGroup
        $this->wpalMock
            ->expects( $this->never() )
            ->method('updateCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('deleteCTCGroup');
        $this->wpalMock
            ->expects( $this->never() )
            ->method('unattachCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('getUnattachedCTCGroups');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('attachCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('createAttachedCTCGroup');

        /**
         * Update people section
         */

        /** @var CTCI_PersonInterface[] $people */
        $people = array(
            '1' => new CTCI_Person( 'f1', 1 ),
            '2' => new CTCI_Person( 'f1', 2 ),
            '3' => new CTCI_Person( 'f1', 3 ),
            '4' => new CTCI_Person( 'f1', 4 ) // we'll call this the new person
        );
        $ctcPeople = array();
        foreach ( $people as $person ) {
            $person
                ->setNameFormat('F L')
                ->setFirstName( 'Person' )
                ->setLastName( $person->id() )
                ->setEmail( 'person' . $person->id() . '@test.com' );

            $ctcPerson = new CTCI_CTCPerson();
            $ctcPerson->setId( $person->id() );
            $ctcPerson->setName( $person->getName() );
            $ctcPerson->setEmail( $person->getEmail() );
            $ctcPeople[ $person->id() ] = $ctcPerson;
        }
        // and add each person to a group
        $people['1']->addGroup( $group1 );
        $people['2']->addGroup( $group2 );
        $people['3']->addGroup( $group2 );
        $people['4']->addGroup( $group1 );

        $this->peopleDataProviderMock
            ->expects( $this->once() )
            ->method('getPeople')
            ->will( $this->returnValue( $people ) );

        $this->wpalMock
            ->expects( $this->once() )
            ->method('getCTCPeopleAttachedViaProvider')
            ->with( $this->equalTo('f1') )
            ->will( $this->returnValue( array( $ctcPeople['1'], $ctcPeople['2'], $ctcPeople['3'] ) ) );

        $this->wpalMock
            ->expects( $this->exactly(3) )
            ->method('getAttachedPersonId')
            ->will( $this->returnValueMap( array(
                array( $ctcPeople[1], '1' ),
                array( $ctcPeople[2], '2' ),
                array( $ctcPeople[3], '3' ),
            )));

        $this->wpalMock
            ->expects( $this->never(4) )
            ->method('setCTCPersonsGroups');

        // update person called on ctc people 1, 2 and 3
        $expArguments_updateCTCPerson = array( $ctcPeople[1], $ctcPeople[2], $ctcPeople[3] );
        $this->wpalMock
            ->expects( $this->exactly(3) )
            ->method('updateCTCPerson')
            ->will( $this->returnCallback(
                function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_updateCTCPerson ) {
                    if(($key = array_search($param, $expArguments_updateCTCPerson)) !== false) {
                        // remove the param to test that all arguments are called with
                        unset($expArguments_updateCTCPerson[$key]);
                    }
                    return null;
                }
            ));

        // no previously attached people to deal with here...
        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'unattachCTCPerson' );
        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'deleteCTCPerson' );
        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'unpublishCTCPerson' );


        // person 4 is new - not be attached to an existing person
        $this->wpalMock
            ->expects( $this->once() )
            ->method('getUnattachedCTCPeople')
            ->will( $this->returnValue( array() ) );

        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'attachCTCPerson' );

        /* protected function createNewCTCPerson */

        // for person 4 who is new
        $this->wpalMock
            ->expects( $this->once() )
            ->method('createAttachedCTCPerson')
            ->with( $this->equalTo( $people[4] ) )
            ->will( $this->returnValue( $ctcPeople[4] ) );

        // the call to setCTCPersonsGroups is defined above amongst the other ones

        /**
         * Act!
         */
        $this->sutPeopleSync->run();

        // assert expected argument arrays are empty
        $this->assertEmpty( $expArguments_updateCTCPerson );

    }

    public function testRun_4AttachedPeople1RemovedUnpublished_2AttachedGroups_SyncGroupsTrue() {
        $this->peopleDataProviderMock
            ->expects( $this->any() )
            ->method('syncGroups')
            ->will( $this->returnValue( true ) );

        $this->peopleDataProviderMock
            ->expects( $this->any() )
            ->method('deleteUnattachedPeople')
            ->will( $this->returnValue( false ) );

        /**
         * updateGroups
         */

        // new groups
        $group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
        $group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );
        $this->peopleDataProviderMock
            ->expects( $this->once() )
            ->method('getGroups')
            ->will( $this->returnValue( array(
                1 => $group1,
                2 => $group2,
            )));

        $ctcGroup1 = new CTCI_CTCGroup( 11, 'Group 1', 'desc' );
        $ctcGroup1->setAttachedGroup( 'f1', 1 );
        $ctcGroup2 = new CTCI_CTCGroup( 12, 'Group 2', 'desc' );
        $ctcGroup2->setAttachedGroup( 'f1', 2 );

        // both groups exist and are attached to the service provider
        $this->wpalMock
            ->expects( $this->once() )
            ->method('getCTCGroupsAttachedViaProvider')
            ->with( 'f1' )
            ->will( $this->returnValue( array( $ctcGroup1, $ctcGroup2 ) ) );

        // check the arguments called on updateCTCGroup
        $expectedArguments_updateCTCGroup = array( $ctcGroup1, $ctcGroup2 );
        $this->wpalMock
            ->expects( $this->exactly(2) )
            ->method('updateCTCGroup')
            ->with( $this->logicalOr( $ctcGroup1, $ctcGroup2 ), $this->logicalOr( $group1, $group2 ) )
            ->will( $this->returnCallback(
                function( CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group ) use (
                    &$expectedArguments_updateCTCGroup
                ) {
                    if(($key = array_search($ctcGroup, $expectedArguments_updateCTCGroup)) !== false) {
                        // remove the param to test that all arguments are called with
                        unset($expectedArguments_updateCTCGroup[$key]);
                    }
                    if ( ( $ctcGroup->id() !== 11 && $ctcGroup->id() !== 12 ) ||
                        ( $ctcGroup->id() === 11 && $group->id() !== 1 ) ||
                        ( $ctcGroup->id() === 12 && $group->id() !== 2 ) ) {
                        throw new Exception( 'updateCTCGroup called with incorrect arguments' );
                    }
                }
            ));

        $this->wpalMock
            ->expects( $this->never() )
            ->method('deleteCTCGroup');
        $this->wpalMock
            ->expects( $this->never() )
            ->method('unattachCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('getUnattachedCTCGroups');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('attachCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('createAttachedCTCGroup');

        /**
         * Update people section
         */

        /** @var CTCI_PersonInterface[] $people */
        $people = array(
            '1' => new CTCI_Person( 'f1', 1 ), // this person to be removed
            '2' => new CTCI_Person( 'f1', 2 ),
            '3' => new CTCI_Person( 'f1', 3 ),
            '4' => new CTCI_Person( 'f1', 4 )
        );
        $ctcPeople = array();
        foreach ( $people as $person ) {
            $person
                ->setNameFormat('F L')
                ->setFirstName( 'Person' )
                ->setLastName( $person->id() )
                ->setEmail( 'person' . $person->id() . '@test.com' );

            $ctcPerson = new CTCI_CTCPerson();
            $ctcPerson->setId( $person->id() );
            $ctcPerson->setName( $person->getName() );
            $ctcPerson->setEmail( $person->getEmail() );
            $ctcPeople[ $person->id() ] = $ctcPerson;
        }
        // and add each person to a group
        $people['1']->addGroup( $group1 );
        $people['2']->addGroup( $group2 );
        $people['3']->addGroup( $group2 );
        $people['4']->addGroup( $group1 );

        $this->peopleDataProviderMock
            ->expects( $this->once() )
            ->method('getPeople')
            ->will( $this->returnValue( array(
                // not person 1 as they have been removed
                '2' => $people[2],
                '3' => $people[3],
                '4' => $people[4]
            ) ) );

        $this->wpalMock
            ->expects( $this->once() )
            ->method('getCTCPeopleAttachedViaProvider')
            ->with( $this->equalTo('f1') )
            ->will( $this->returnValue( array( $ctcPeople['1'], $ctcPeople['2'], $ctcPeople['3'], $ctcPeople['4'] ) ) );

        $this->wpalMock
            ->expects( $this->exactly(4) )
            ->method('getAttachedPersonId')
            ->will( $this->returnValueMap( array(
                array( $ctcPeople[1], '1' ),
                array( $ctcPeople[2], '2' ),
                array( $ctcPeople[3], '3' ),
                array( $ctcPeople[4], '4' ),
            )));

        // check setCTCPersonsGroups called on ctc people 2, 3, 4
        // called in syncCTCPerson
        $expArguments_setCTCPersonsGroups = array( $ctcPeople[2], $ctcPeople[3], $ctcPeople[4] );
        $argumentMismatch_setCTCPersonsGroups = false;
        $this->wpalMock
            ->expects( $this->exactly(3) )
            ->method('setCTCPersonsGroups')
            ->with( $this->logicalOr(
                $this->equalTo( $ctcPeople[2] ),
                $this->equalTo( $ctcPeople[3] ),
                $this->equalTo( $ctcPeople[4] )
            ), $this->logicalOr(
                $this->equalTo( $people['2']->getGroups() ),
                $this->equalTo( $people['3']->getGroups() ),
                $this->equalTo( $people['4']->getGroups() )
            ) )->will( $this->returnCallback(
                function( CTCI_CTCPersonInterface $param1, $param2 ) use (
                    &$expArguments_setCTCPersonsGroups, &$argumentMismatch_setCTCPersonsGroups, $people
                ) {
                    if(($key = array_search($param1, $expArguments_setCTCPersonsGroups)) !== false) {
                        // remove the param to test that all arguments are called with
                        unset($expArguments_setCTCPersonsGroups[$key]);
                        // the second param should be the same as the groups returned from the person
                        // with the same id
                        if ( $param2 !== $people[ $param1->id() ]->getGroups() ) {
                            $argumentMismatch_setCTCPersonsGroups = true;
                        }
                    }
                    return null;
                }
            ));

        // update person called on ctc people 2, 3 and 4
        $expArguments_updateCTCPerson = array( $ctcPeople[2], $ctcPeople[3], $ctcPeople[4] );
        $this->wpalMock
            ->expects( $this->exactly(3) )
            ->method('updateCTCPerson')
            ->will( $this->returnCallback(
                function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_updateCTCPerson ) {
                    if(($key = array_search($param, $expArguments_updateCTCPerson)) !== false) {
                        // remove the param to test that all arguments are called with
                        unset($expArguments_updateCTCPerson[$key]);
                    }
                    return null;
                }
            ));

        // now we handle the removed person 1
        $this->wpalMock
            ->expects( $this->once() )
            ->method( 'unattachCTCPerson' )
            ->with( $this->equalTo( $ctcPeople[1] ) );
        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'deleteCTCPerson' );
        $this->wpalMock
            ->expects( $this->once() )
            ->method( 'unpublishCTCPerson' )
            ->with( $this->equalTo( $ctcPeople[1] ) );


        // no new people to deal with
        $this->wpalMock
            ->expects( $this->never() )
            ->method('getUnattachedCTCPeople');

        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'attachCTCPerson' );

        /* protected function createNewCTCPerson */

        // for person 4 who is new
        $this->wpalMock
            ->expects( $this->never() )
            ->method('createAttachedCTCPerson');

        /**
         * Act!
         */
        $this->sutPeopleSync->run();

        // assert expected argument arrays are empty
        $this->assertEmpty( $expArguments_updateCTCPerson );
        $this->assertEmpty( $expectedArguments_updateCTCGroup );
        $this->assertEmpty( $expArguments_setCTCPersonsGroups );
        $this->assertFalse( $argumentMismatch_setCTCPersonsGroups );

    }

    public function testRun_4AttachedPeople1RemovedDeleted_2AttachedGroups_SyncGroupsTrue() {
        $this->peopleDataProviderMock
            ->expects( $this->any() )
            ->method('syncGroups')
            ->will( $this->returnValue( true ) );

        $this->peopleDataProviderMock
            ->expects( $this->any() )
            ->method('deleteUnattachedPeople')
            ->will( $this->returnValue( true ) );

        /**
         * updateGroups
         */

        // new groups
        $group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
        $group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );
        $this->peopleDataProviderMock
            ->expects( $this->once() )
            ->method('getGroups')
            ->will( $this->returnValue( array(
                1 => $group1,
                2 => $group2,
            )));

        $ctcGroup1 = new CTCI_CTCGroup( 11, 'Group 1', 'desc' );
        $ctcGroup1->setAttachedGroup( 'f1', 1 );
        $ctcGroup2 = new CTCI_CTCGroup( 12, 'Group 2', 'desc' );
        $ctcGroup2->setAttachedGroup( 'f1', 2 );

        // both groups exist and are attached to the service provider
        $this->wpalMock
            ->expects( $this->once() )
            ->method('getCTCGroupsAttachedViaProvider')
            ->with( 'f1' )
            ->will( $this->returnValue( array( $ctcGroup1, $ctcGroup2 ) ) );

        // check the arguments called on updateCTCGroup
        $expectedArguments_updateCTCGroup = array( $ctcGroup1, $ctcGroup2 );
        $this->wpalMock
            ->expects( $this->exactly(2) )
            ->method('updateCTCGroup')
            ->with( $this->logicalOr( $ctcGroup1, $ctcGroup2 ), $this->logicalOr( $group1, $group2 ) )
            ->will( $this->returnCallback(
                function( CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group ) use (
                    &$expectedArguments_updateCTCGroup
                ) {
                    if(($key = array_search($ctcGroup, $expectedArguments_updateCTCGroup)) !== false) {
                        // remove the param to test that all arguments are called with
                        unset($expectedArguments_updateCTCGroup[$key]);
                    }
                    if ( ( $ctcGroup->id() !== 11 && $ctcGroup->id() !== 12 ) ||
                        ( $ctcGroup->id() === 11 && $group->id() !== 1 ) ||
                        ( $ctcGroup->id() === 12 && $group->id() !== 2 ) ) {
                        throw new Exception( 'updateCTCGroup called with incorrect arguments' );
                    }
                }
            ));

        $this->wpalMock
            ->expects( $this->never() )
            ->method('deleteCTCGroup');
        $this->wpalMock
            ->expects( $this->never() )
            ->method('unattachCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('getUnattachedCTCGroups');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('attachCTCGroup');

        $this->wpalMock
            ->expects( $this->never() )
            ->method('createAttachedCTCGroup');

        /**
         * Update people section
         */

        /** @var CTCI_PersonInterface[] $people */
        $people = array(
            '1' => new CTCI_Person( 'f1', 1 ), // this person to be removed
            '2' => new CTCI_Person( 'f1', 2 ),
            '3' => new CTCI_Person( 'f1', 3 ),
            '4' => new CTCI_Person( 'f1', 4 )
        );
        $ctcPeople = array();
        foreach ( $people as $person ) {
            $person
                ->setNameFormat('F L')
                ->setFirstName( 'Person' )
                ->setLastName( $person->id() )
                ->setEmail( 'person' . $person->id() . '@test.com' );

            $ctcPerson = new CTCI_CTCPerson();
            $ctcPerson->setId( $person->id() );
            $ctcPerson->setName( $person->getName() );
            $ctcPerson->setEmail( $person->getEmail() );
            $ctcPeople[ $person->id() ] = $ctcPerson;
        }
        // and add each person to a group
        $people['1']->addGroup( $group1 );
        $people['2']->addGroup( $group2 );
        $people['3']->addGroup( $group2 );
        $people['4']->addGroup( $group1 );

        $this->peopleDataProviderMock
            ->expects( $this->once() )
            ->method('getPeople')
            ->will( $this->returnValue( array(
                // not person 1 as they have been removed
                '2' => $people[2],
                '3' => $people[3],
                '4' => $people[4]
            ) ) );

        $this->wpalMock
            ->expects( $this->once() )
            ->method('getCTCPeopleAttachedViaProvider')
            ->with( $this->equalTo('f1') )
            ->will( $this->returnValue( array( $ctcPeople['1'], $ctcPeople['2'], $ctcPeople['3'], $ctcPeople['4'] ) ) );

        $this->wpalMock
            ->expects( $this->exactly(4) )
            ->method('getAttachedPersonId')
            ->will( $this->returnValueMap( array(
                array( $ctcPeople[1], '1' ),
                array( $ctcPeople[2], '2' ),
                array( $ctcPeople[3], '3' ),
                array( $ctcPeople[4], '4' ),
            )));

        // check setCTCPersonsGroups called on ctc people 2, 3, 4
        // called in syncCTCPerson
        $expArguments_setCTCPersonsGroups = array( $ctcPeople[2], $ctcPeople[3], $ctcPeople[4] );
        $argumentMismatch_setCTCPersonsGroups = false;
        $this->wpalMock
            ->expects( $this->exactly(3) )
            ->method('setCTCPersonsGroups')
            ->with( $this->logicalOr(
                $this->equalTo( $ctcPeople[2] ),
                $this->equalTo( $ctcPeople[3] ),
                $this->equalTo( $ctcPeople[4] )
            ), $this->logicalOr(
                $this->equalTo( $people['2']->getGroups() ),
                $this->equalTo( $people['3']->getGroups() ),
                $this->equalTo( $people['4']->getGroups() )
            ) )->will( $this->returnCallback(
                function( CTCI_CTCPersonInterface $param1, $param2 ) use (
                    &$expArguments_setCTCPersonsGroups, &$argumentMismatch_setCTCPersonsGroups, $people
                ) {
                    if(($key = array_search($param1, $expArguments_setCTCPersonsGroups)) !== false) {
                        // remove the param to test that all arguments are called with
                        unset($expArguments_setCTCPersonsGroups[$key]);
                        // the second param should be the same as the groups returned from the person
                        // with the same id
                        if ( $param2 !== $people[ $param1->id() ]->getGroups() ) {
                            $argumentMismatch_setCTCPersonsGroups = true;
                        }
                    }
                    return null;
                }
            ));

        // update person called on ctc people 2, 3 and 4
        $expArguments_updateCTCPerson = array( $ctcPeople[2], $ctcPeople[3], $ctcPeople[4] );
        $this->wpalMock
            ->expects( $this->exactly(3) )
            ->method('updateCTCPerson')
            ->will( $this->returnCallback(
                function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_updateCTCPerson ) {
                    if(($key = array_search($param, $expArguments_updateCTCPerson)) !== false) {
                        // remove the param to test that all arguments are called with
                        unset($expArguments_updateCTCPerson[$key]);
                    }
                    return null;
                }
            ));

        // now we handle the removed person 1
        $this->wpalMock
            ->expects( $this->once() )
            ->method( 'unattachCTCPerson' )
            ->with( $this->equalTo( $ctcPeople[1] ) );
        $this->wpalMock
            ->expects( $this->once() )
            ->method( 'deleteCTCPerson' )
            ->with( $this->equalTo( $ctcPeople[1] ) );
        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'unpublishCTCPerson' );


        // no new people to deal with
        $this->wpalMock
            ->expects( $this->never() )
            ->method('getUnattachedCTCPeople');

        $this->wpalMock
            ->expects( $this->never() )
            ->method( 'attachCTCPerson' );

        /* protected function createNewCTCPerson */

        // for person 4 who is new
        $this->wpalMock
            ->expects( $this->never() )
            ->method('createAttachedCTCPerson');

        /**
         * Act!
         */
        $this->sutPeopleSync->run();

        // assert expected argument arrays are empty
        $this->assertEmpty( $expArguments_updateCTCPerson );
        $this->assertEmpty( $expectedArguments_updateCTCGroup );
        $this->assertEmpty( $expArguments_setCTCPersonsGroups );
        $this->assertFalse( $argumentMismatch_setCTCPersonsGroups );

    }

	public function testRun_4AttachedPeople_2AttachedGroups_1GroupRemoved_SyncGroupsTrue() {
		/**
		 * Scenario with 2 groups of 2 people previously synced. One of the groups has been removed from the list
		 * of groups to sync. So we expect:
		 *
		 * getGroups to return 1 group
		 * getCTCGroupsAttachedViaProvider to return 2 groups
		 * updateCTCGroup on the first group
		 * deleteCTCGroup on the second group (assuming that deleteUnattachedGroups option is set to true)
		 * syncCTCPerson to be called on the people in group still to be synced
		 * unattachCTCPerson, unpublishCTCPerson to be called on the people in the group no longer synced
		 */

		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('syncGroups')
			->will( $this->returnValue( true ) );

		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('deleteUnattachedPeople')
			->will( $this->returnValue( false ) );

		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('deleteUnattachedGroups')
			->will( $this->returnValue( true ) );

		/**
		 * updateGroups
		 */

		// new groups
		$group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
		$group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );
		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getGroups')
			->will( $this->returnValue( array(
				1 => $group1,
			)));

		$ctcGroup1 = new CTCI_CTCGroup( 11, 'Group 1', 'desc' );
		$ctcGroup1->setAttachedGroup( 'f1', 1 );
		$ctcGroup2 = new CTCI_CTCGroup( 12, 'Group 2', 'desc' );
		$ctcGroup2->setAttachedGroup( 'f1', 2 );

		// both groups exist and are attached to the service provider
		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCGroupsAttachedViaProvider')
			->with( 'f1' )
			->will( $this->returnValue( array( $ctcGroup1, $ctcGroup2 ) ) );

		// check the arguments called on updateCTCGroup
		//$expectedArguments_updateCTCGroup = array( $ctcGroup1 );
		$this->wpalMock
			->expects( $this->once() )
			->method('updateCTCGroup')
			->with( $this->equalTo( $ctcGroup1 ), $this->equalTo( $group1 ) );

		$this->wpalMock
			->expects( $this->once() )
			->method('deleteCTCGroup')
			->with( $this->equalTo( $ctcGroup2 ) );

		$this->wpalMock
			->expects( $this->never() )
			->method('unattachCTCGroup');

		$this->wpalMock
			->expects( $this->never() )
			->method('getUnattachedCTCGroups');

		$this->wpalMock
			->expects( $this->never() )
			->method('attachCTCGroup');

		$this->wpalMock
			->expects( $this->never() )
			->method('createAttachedCTCGroup');

		/**
		 * Update people section
		 */

		/** @var CTCI_PersonInterface[] $people */
		$people = array(
			'1' => new CTCI_Person( 'f1', 1 ),
			'2' => new CTCI_Person( 'f1', 2 ),
			'3' => new CTCI_Person( 'f1', 3 ),
			'4' => new CTCI_Person( 'f1', 4 )
		);
		$ctcPeople = array();
		foreach ( $people as $person ) {
			$person
				->setNameFormat('F L')
				->setFirstName( 'Person' )
				->setLastName( $person->id() )
				->setEmail( 'person' . $person->id() . '@test.com' );

			$ctcPerson = new CTCI_CTCPerson();
			$ctcPerson->setId( $person->id() );
			$ctcPerson->setName( $person->getName() );
			$ctcPerson->setEmail( $person->getEmail() );
			$ctcPeople[ $person->id() ] = $ctcPerson;
		}
		// and add each person to a group
		$people['1']->addGroup( $group1 );
		$people['2']->addGroup( $group1 );
		$people['3']->addGroup( $group2 );
		$people['4']->addGroup( $group2 );

		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getPeople')
			->will( $this->returnValue( array(
				// person 3 and 4 in group no longer to be synced
				'1' => $people[1],
				'2' => $people[2],
			) ) );

		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCPeopleAttachedViaProvider')
			->with( $this->equalTo('f1') )
			->will( $this->returnValue( array( $ctcPeople['1'], $ctcPeople['2'], $ctcPeople['3'], $ctcPeople['4'] ) ) );

		$this->wpalMock
			->expects( $this->exactly(4) )
			->method('getAttachedPersonId')
			->will( $this->returnValueMap( array(
				array( $ctcPeople[1], '1' ),
				array( $ctcPeople[2], '2' ),
				array( $ctcPeople[3], '3' ),
				array( $ctcPeople[4], '4' ),
			)));

		// check setCTCPersonsGroups called on ctc people 1, 2
		// called in syncCTCPerson
		$expArguments_setCTCPersonsGroups = array( $ctcPeople[1], $ctcPeople[2] );
		$argumentMismatch_setCTCPersonsGroups = false;
		$this->wpalMock
			->expects( $this->exactly(2) )
			->method('setCTCPersonsGroups')
			->with( $this->logicalOr(
				$this->equalTo( $ctcPeople[1] ),
				$this->equalTo( $ctcPeople[2] )
			), $this->logicalOr(
				$this->equalTo( $people['1']->getGroups() ),
				$this->equalTo( $people['2']->getGroups() )
			) )->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param1, $param2 ) use (
					&$expArguments_setCTCPersonsGroups, &$argumentMismatch_setCTCPersonsGroups, $people
				) {
					if(($key = array_search($param1, $expArguments_setCTCPersonsGroups)) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_setCTCPersonsGroups[$key]);
						// the second param should be the same as the groups returned from the person
						// with the same id
						if ( $param2 !== $people[ $param1->id() ]->getGroups() ) {
							$argumentMismatch_setCTCPersonsGroups = true;
						}
					}
					return null;
				}
			));

		// update person called on ctc people 1 and 2
		$expArguments_updateCTCPerson = array( $ctcPeople[1], $ctcPeople[2] );
		$this->wpalMock
			->expects( $this->exactly(2) )
			->method('updateCTCPerson')
			->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_updateCTCPerson ) {
					if(($key = array_search($param, $expArguments_updateCTCPerson)) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_updateCTCPerson[$key]);
					}
					return null;
				}
			));

		// now we handle the removed people 3 and 4
		$expArguments_unattachCTCPerson = array( $ctcPeople[3], $ctcPeople[4] );
		$this->wpalMock
			->expects( $this->exactly(2) )
			->method( 'unattachCTCPerson' )
			->with( $this->logicalOr( $this->equalTo( $ctcPeople[3] ), $this->equalTo( $ctcPeople[4] ) ) )
			->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_unattachCTCPerson ) {
					if(($key = array_search($param, $expArguments_unattachCTCPerson )) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_unattachCTCPerson [$key]);
					}
					return null;
				}
			));

		$expArguments_unpublishCTCPerson = array( $ctcPeople[3], $ctcPeople[4] );
		$this->wpalMock
			->expects( $this->exactly(2) )
			->method( 'unpublishCTCPerson' )
			->with( $this->logicalOr( $this->equalTo( $ctcPeople[3] ), $this->equalTo( $ctcPeople[4] ) ) )
			->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_unpublishCTCPerson ) {
					if(($key = array_search($param, $expArguments_unpublishCTCPerson )) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_unpublishCTCPerson[$key]);
					}
					return null;
				}
			));

		$this->wpalMock
			->expects( $this->never() )
			->method( 'deleteCTCPerson' );

		// no new people to deal with
		$this->wpalMock
			->expects( $this->never() )
			->method('getUnattachedCTCPeople');

		$this->wpalMock
			->expects( $this->never() )
			->method( 'attachCTCPerson' );

		/* protected function createNewCTCPerson */

		$this->wpalMock
			->expects( $this->never() )
			->method('createAttachedCTCPerson');

		/**
		 * Act!
		 */
		$this->sutPeopleSync->run();

		// assert expected argument arrays are empty
		$this->assertEmpty( $expArguments_updateCTCPerson );
		$this->assertEmpty( $expArguments_setCTCPersonsGroups );
		$this->assertFalse( $argumentMismatch_setCTCPersonsGroups );
		$this->assertEmpty( $expArguments_unattachCTCPerson  );
		$this->assertEmpty( $expArguments_unpublishCTCPerson );

	}

	public function testRun_2AttachedPeople_1AttachedGroup_1NewGroup_SyncGroupsTrue() {
		/**
		 * Scenario with 1 group of 2 people previously synced. One more group with 2 people has just been added to the
		 * groups to sync, where 1 of those people has an existing CTC record. So we expect:
		 *
		 * getGroups to return 2 groups
		 * getCTCGroupsAttachedViaProvider to return 1 group
		 * updateCTCGroup on the existing group
		 * createAttachedCTCGroup on the new group
		 * setCTCPersonsGroups, updateCTCPerson to be called on the people in group previously synced
		 * createAttachedCTCPerson, setCTCPersonGroups to be called on new person in new group
		 * attachCTCPerson, publishCTCPerson, setCTCPersonsGroups, updateCTCPerson on existing person in new group
		 */

		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('syncGroups')
			->will( $this->returnValue( true ) );

		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('deleteUnattachedPeople')
			->will( $this->returnValue( false ) );

		$this->peopleDataProviderMock
			->expects( $this->any() )
			->method('deleteUnattachedGroups')
			->will( $this->returnValue( true ) );

		/**
		 * updateGroups
		 */

		// groups to sync
		$group1 = new CTCI_PeopleGroup( 'f1', 1, 'Group 1', 'desc' );
		$group2 = new CTCI_PeopleGroup( 'f1', 2, 'Group 2', 'desc' );
		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getGroups')
			->will( $this->returnValue( array(
				1 => $group1,
				2 => $group2,
			)));

		// existing groups
		$ctcGroup1 = new CTCI_CTCGroup( 11, 'Group 1', 'desc' );
		$ctcGroup1->setAttachedGroup( 'f1', 1 );

		// both groups exist and are attached to the service provider
		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCGroupsAttachedViaProvider')
			->with( 'f1' )
			->will( $this->returnValue( array( $ctcGroup1 ) ) );

		// update attached group
		$this->wpalMock
			->expects( $this->once() )
			->method('updateCTCGroup')
			->with( $this->equalTo( $ctcGroup1 ), $this->equalTo( $group1 ) );

		$this->wpalMock
			->expects( $this->never() )
			->method('deleteCTCGroup');

		$this->wpalMock
			->expects( $this->never() )
			->method('unattachCTCGroup');

		// return some other group that should be ignored
		$this->wpalMock
			->expects( $this->once() )
			->method('getUnattachedCTCGroups')
			->will( $this->returnValue( array( new CTCI_CTCGroup(27, 'Group 27', '' ))));

		$this->wpalMock
			->expects( $this->never() )
			->method('attachCTCGroup');

		$this->wpalMock
			->expects( $this->once() )
			->method('createAttachedCTCGroup')
			->with( $this->equalTo( $group2 ) );

		/**
		 * Update people section
		 */

		/** @var CTCI_PersonInterface[] $people */
		$people = array(
			'1' => new CTCI_Person( 'f1', 1 ),
			'2' => new CTCI_Person( 'f1', 2 ),
			'3' => new CTCI_Person( 'f1', 3 ),
			'4' => new CTCI_Person( 'f1', 4 )
		);
		$ctcPeople = array();
		foreach ( $people as $person ) {
			$person
				->setNameFormat('F L')
				->setFirstName( 'Person' )
				->setLastName( $person->id() )
				->setEmail( 'person' . $person->id() . '@test.com' );

			$ctcPerson = new CTCI_CTCPerson();
			$ctcPerson->setId( $person->id() );
			$ctcPerson->setName( $person->getName() );
			$ctcPerson->setEmail( $person->getEmail() );
			$ctcPeople[ $person->id() ] = $ctcPerson;
		}
		// and add each person to a group
		$people['1']->addGroup( $group2 );
		$people['2']->addGroup( $group1 );
		$people['3']->addGroup( $group2 );
		$people['4']->addGroup( $group1 );

		$this->peopleDataProviderMock
			->expects( $this->once() )
			->method('getPeople')
			->will( $this->returnValue( array(
				'1' => $people[1],
				'2' => $people[2],
				'3' => $people[3],
				'4' => $people[4],
			) ) );

		$this->wpalMock
			->expects( $this->once() )
			->method('getCTCPeopleAttachedViaProvider')
			->with( $this->equalTo('f1') )
			// group 1 people
			->will( $this->returnValue( array( $ctcPeople['2'], $ctcPeople['4'] ) ) );

		$this->wpalMock
			->expects( $this->exactly(2) )
			->method('getAttachedPersonId')
			->will( $this->returnValueMap( array(
				array( $ctcPeople[2], '2' ),
				array( $ctcPeople[4], '4' ),
			)));

		// check setCTCPersonsGroups called on all of them
		// for ctc people 2, 4, in the previously synced group, these occur in loop handling attached people
		// for ctc person 1, this happens in sync routine in loop for new people
		// for ctc person 3, this occurs in createNewCTCPerson
		// called in syncCTCPerson
		$expArguments_setCTCPersonsGroups = array( $ctcPeople[1], $ctcPeople[2], $ctcPeople[3], $ctcPeople[4] );
		$argumentMismatch_setCTCPersonsGroups = false;
		$this->wpalMock
			->expects( $this->exactly(4) )
			->method('setCTCPersonsGroups')
			->with( $this->logicalOr(
				$this->equalTo( $ctcPeople[1] ),
				$this->equalTo( $ctcPeople[2] ),
				$this->equalTo( $ctcPeople[3] ),
				$this->equalTo( $ctcPeople[4] )
			), $this->logicalOr(
				$this->equalTo( $people['1']->getGroups() ),
				$this->equalTo( $people['2']->getGroups() ),
				$this->equalTo( $people['3']->getGroups() ),
				$this->equalTo( $people['4']->getGroups() )
			) )->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param1, $param2 ) use (
					&$expArguments_setCTCPersonsGroups, &$argumentMismatch_setCTCPersonsGroups, $people
				) {
					if(($key = array_search($param1, $expArguments_setCTCPersonsGroups)) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_setCTCPersonsGroups[$key]);
						// the second param should be the same as the groups returned from the person
						// with the same id
						if ( $param2 !== $people[ $param1->id() ]->getGroups() ) {
							$argumentMismatch_setCTCPersonsGroups = true;
						}
					}
					return null;
				}
			));

		// update person called on ctc people 2, 3 and 4
		// called on 3 as an existing record just attached
		$expArguments_updateCTCPerson = array( $ctcPeople[2], $ctcPeople[3], $ctcPeople[4] );
		$this->wpalMock
			->expects( $this->exactly(3) )
			->method('updateCTCPerson')
			->with( $this->logicalOr(
				$this->equalTo( $ctcPeople[2] ),
				$this->equalTo( $ctcPeople[3] ),
				$this->equalTo( $ctcPeople[4] )
			))
			->will( $this->returnCallback(
				function( CTCI_CTCPersonInterface $param ) use ( &$expArguments_updateCTCPerson ) {
					if(($key = array_search($param, $expArguments_updateCTCPerson)) !== false) {
						// remove the param to test that all arguments are called with
						unset($expArguments_updateCTCPerson[$key]);
					}
					return null;
				}
			));

		$this->wpalMock
			->expects( $this->never() )
			->method( 'unattachCTCPerson' );

		$this->wpalMock
			->expects( $this->never() )
			->method( 'deleteCTCPerson' );

		$this->wpalMock
			->expects( $this->never() )
			->method( 'unpublishCTCPerson' );

		// one of the new people we will attach to an existing

		// the one we will attach is last in the list, so this will return both unattached records on each call
		$existingCTCPerson1 = new CTCI_CTCPerson();
		$existingCTCPerson1->setId(100)->setName('Unrelated other');
		//$existingCTCPerson2 = new CTCI_CTCPerson();
		// these details should match above
		//$existingCTCPerson2->setId(3)->setName('Person 3')->setEmail( 'person3@test.com' );
		$this->wpalMock
			->expects( $this->exactly(2) )
			->method('getUnattachedCTCPeople')
			->will($this->returnValue( array( $existingCTCPerson1, $ctcPeople[3] ) ));

		/* protected function createNewCTCPerson for person 1 who is new */

		$this->wpalMock
			->expects( $this->once() )
			->method('createAttachedCTCPerson')
			->with( $this->equalTo( $people[1] ) )
			->will( $this->returnValue( $ctcPeople[1] ) );

		// the setCTCPersonsGroups calls within createNewCTCPerson is included above

		// person 3, new to be synced, but attach to existing

		$this->wpalMock
			->expects( $this->once() )
			->method( 'attachCTCPerson' )
			->with( $this->equalTo( $ctcPeople[3] ), $this->equalTo( $people[3] ) )
			->will( $this->returnValue( true ) );

		$this->wpalMock
			->expects( $this->once() )
			->method( 'publishCTCPerson' )
			->with( $this->equalTo( $ctcPeople[3] ) );

		/**
		 * Act!
		 */
		$this->sutPeopleSync->run();

		// assert expected argument arrays are empty
		$this->assertEmpty( $expArguments_updateCTCPerson );
		$this->assertEmpty( $expArguments_setCTCPersonsGroups );
		$this->assertFalse( $argumentMismatch_setCTCPersonsGroups );

	}
}
 