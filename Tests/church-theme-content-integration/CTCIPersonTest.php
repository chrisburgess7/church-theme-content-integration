<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 6/03/14
 * Time: 5:18 PM
 */

require_once dirname(__FILE__) . '/../../church-theme-content-integration/CTCI_Person.php';

class CTCIPersonTest extends PHPUnit_Framework_TestCase
{
    /** @var CTCI_PersonInterface */
    protected $sut;

    public function setUp()
    {
        $this->sut = new CTCI_Person();
    }

    public static function getNameData()
    {
        return array(
            array(
                array('', '', 'Christopher', '', 'Garry', 'Burgess', ''), 'F L', 'Christopher Burgess'
            ),
            array(
                array('', '', 'Christopher', 'Chris', '', 'Burgess', ''), 'L F', 'Burgess Christopher'
            ),
            array(
                array('', '', 'Christopher', '', '', 'Burgess', ''), 'L, F', 'Burgess, Christopher'
            ),
            array(
                array('Mr', '', 'Christopher', '', '', 'Burgess', ''), 'T F L', 'Mr Christopher Burgess'
            ),
            array(
                array('Mr', '', 'Christopher', '', 'Garry', 'Burgess', ''), 'T. F L', 'Mr. Christopher Burgess'
            ),
            array(
                array('Mr', '', 'Christopher', 'Chris', '', 'Burgess', ''), 'G L', 'Chris Burgess'
            ),
            array(
                array('Mr', '', 'Christopher', 'Chris', 'Garry', 'Burgess', ''), 'T. G L', 'Mr. Chris Burgess'
            ),
            array(
                array('Mr', '', 'Christopher', 'Chris', 'Garry', 'Burgess', ''), 'F M L', 'Christopher Garry Burgess'
            ),
            array(
                array('Mr', '', 'Christopher', 'Chris', 'Garry', 'Burgess', ''), 'L, F M', 'Burgess, Christopher Garry'
            ),
            array(
                array('Mr', '', 'Christopher', 'Chris', 'Garry', 'Burgess', 'Ph.D'), 'T. F M L S', 'Mr. Christopher Garry Burgess Ph.D'
            ),
            array(
                array('Mr', 'Pfx', 'Christopher', 'Chris', 'Garry', 'Burgess', 'Ph.D'), 'P F M L S', 'Pfx Christopher Garry Burgess Ph.D'
            ),
            array(
                array('Mr', 'Pfx', 'Christopher', 'Chris', 'Garry', 'Burgess', 'Ph.D'), 'T. F J L S', 'Mr. Christopher G Burgess Ph.D'
            ),
            array(
                array('Mr', 'Pfx', 'Christopher', 'Chris', 'Garry', 'Burgess', 'Ph.D'), 'T. I. J. K. S', 'Mr. C. G. B. Ph.D'
            ),
            array(
                array('Mr', 'Pfx', 'Christopher', 'Chris', 'Garry', 'Burgess', 'Ph.D'), 'G J L', 'Chris G Burgess'
            ),
            array(
                array('Mr', 'Pfx', 'Christopher', '', 'Garry', 'Burgess', 'Ph.D'), 'G J L', 'Christopher G Burgess'
            ),
            array(
                array('Mr', 'Pfx', 'Christopher', 'Chris', 'Garry', 'Burgess', 'Ph.D'), 'FQ L', 'Christopher "Chris" Burgess'
            ),
            array(
                array('Mr', 'Pfx', 'Christopher', '', 'Garry', 'Burgess', 'Ph.D'), 'FQ L', 'Christopher Burgess'
            )
        );
    }

    /**
     * @dataProvider getNameData
     * @param array $nameValues
     * @param $format
     * @param $expectedResult
     */
    public function testGetName(array $nameValues, $format, $expectedResult)
    {
        $this->sut
            ->setTitle($nameValues[0])
            ->setNamePrefix($nameValues[1])
            ->setFirstName($nameValues[2])
            ->setGoesByName($nameValues[3])
            ->setMiddleName($nameValues[4])
            ->setLastName($nameValues[5])
            ->setNameSuffix($nameValues[6]);

        $actual = $this->sut->getName($format);

        $this->assertEquals($expectedResult, $actual);
    }

    public function testSetNameFormat()
    {
        $this->sut
            ->setTitle('Mr')
            ->setNamePrefix()
            ->setFirstName('Christopher')
            ->setGoesByName('Chris')
            ->setMiddleName('Garry')
            ->setLastName('Burgess')
            ->setNameSuffix();

        $this->sut->setNameFormat('G L');

        $name = $this->sut->getName();

        $this->assertEquals('Chris Burgess', $name);
    }
}
 