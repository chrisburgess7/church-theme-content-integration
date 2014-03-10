<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 8/03/14
 * Time: 3:24 PM
 */

require_once dirname(__FILE__) . '/../CTCI_BaseDatabaseTestCase.php';
require_once dirname(__FILE__) . '/../../church-theme-content-integration/CTCI_WPAL.php';
require_once dirname(__FILE__) . '/../../church-theme-content-integration/CTCI_PeopleGroup.php';
require_once dirname(__FILE__) . '/../../church-theme-content-integration/CTCI_CTCGroup.php';

class WPUnitTestCaseTest extends WP_UnitTestCase
{

}

class CTCI_WPALTest extends CTCI_BaseDatabaseTestCase
{
    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array(
                $this->createMySQLXMLDataSet(dirname(__FILE__) . '/../ctcdata.xml'),
                $this->createMySQLXMLDataSet(dirname(__FILE__) . '/../ctcdata_groupconnectempty.xml'),
            )
        );

    }

    /** @var CTCI_WPALInterface */
    protected $sut;

    public function setUp()
    {
        $this->sut = new CTCI_WPAL();
    }

    public function testAttachCTCGroup()
    {
        // id of 25 matches the Elders group in the sample data
        $ctcGroup = new CTCI_CTCGroup(25, 'CTC Group', '');
        $group = new CTCI_PeopleGroup('f1', '37e21', 'My Group', '');

        $this->sut->attachCTCGroup($ctcGroup, $group);

        $queryTable = $this->getConnection()->createQueryTable(
            CTCI_WPAL::$ctcGroupConnectTable, 'SELECT * FROM ' . CTCI_WPAL::$ctcGroupConnectTable
        );
        $expectedTable = $this->createMySQLXMLDataSet(
            dirname(__FILE__) . '/CTCI_WPALTestData/testAttachCTCGroup_groupconnect.xml'
        )->getTable(CTCI_WPAL::$ctcGroupConnectTable);

        $this->assertDataSetsEqual($expectedTable, $queryTable);
    }
}
 