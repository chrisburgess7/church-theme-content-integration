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
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/fellowship-one/fellowship-one.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/fellowship-one/class-f1-people-data-provider.php';
require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-logger.php';
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
	protected $loggerMock;
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

		$this->loggerMock = $this->getMockBuilder('CTCI_Logger')
			->disableOriginalConstructor()
			->getMock();

		$this->wpalMock = $this->getMockBuilder('CTCI_WPAL')
			->disableOriginalConstructor()
			->getMock();

		$this->sutPeopleSync = new CTCI_PeopleSync( $this->wpalMock, $this->loggerMock );
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
		$this->assertEmpty( $expectedArguments_updateCTCGroup );
		$this->assertEmpty( $expArguments_setCTCPersonsGroups );
		$this->assertFalse( $argumentMismatch_setCTCPersonsGroups );

	}
}
 