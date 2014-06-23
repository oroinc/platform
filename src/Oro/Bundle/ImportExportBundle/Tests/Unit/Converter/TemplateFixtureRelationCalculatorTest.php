<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\ImportExportBundle\Converter\TemplateFixtureRelationCalculator;

class TemplateFixtureRelationCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fixtureRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var TemplateFixtureRelationCalculator
     */
    protected $calculator;

    protected function setUp()
    {
        $this->fixtureRegistry = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calculator = new TemplateFixtureRelationCalculator($this->fixtureRegistry, $this->fieldHelper);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage There is no template fixture registered for "stdClass".
     */
    public function testGetMaxRelatedEntitiesException()
    {
        $entityName = 'stdClass';

        $this->fixtureRegistry->expects($this->once())
            ->method('hasEntityFixture')
            ->with($entityName)
            ->will($this->returnValue(false));

        $this->calculator->getMaxRelatedEntities($entityName, 'test');
    }

    /**
     * @dataProvider calculatorDataProvider
     * @param \ArrayIterator $fixtureData
     * @param string $field
     * @param int $expected
     */
    public function testGetMaxRelatedEntities(\ArrayIterator $fixtureData, $field, $expected)
    {
        $entityName = 'stdClass';

        $fixture = $this->getMock('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface');
        $fixture->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($fixtureData));

        $this->fixtureRegistry->expects($this->once())
            ->method('hasEntityFixture')
            ->with($entityName)
            ->will($this->returnValue(true));
        $this->fixtureRegistry->expects($this->once())
            ->method('getEntityFixture')
            ->with($entityName)
            ->will($this->returnValue($fixture));
        $this->fieldHelper->expects($this->atLeastOnce())
            ->method('getObjectValue')
            ->will(
                $this->returnCallback(
                    function ($obj, $field) {
                        return $obj->$field;
                    }
                )
            );

        $this->assertEquals($expected, $this->calculator->getMaxRelatedEntities($entityName, $field));
    }

    /**
     * @return array
     */
    public function calculatorDataProvider()
    {
        $fixtureOne = new \stdClass();
        $fixtureOne->str = 'test';
        $fixtureOne->emptyRelationArray = array();
        $fixtureOne->emptyIterator = new \ArrayIterator(array());
        $fixtureOne->relationArray = array(1, 2);
        $fixtureOne->relationIterator = new \ArrayIterator(array(1, 2, 3));

        $fixtureTwo = new \stdClass();
        $fixtureTwo->relationIterator = new \ArrayIterator(array(1, 2, 3, 4, 5));

        return array(
            array(new \ArrayIterator(array($fixtureOne)), 'str', 0),
            array(new \ArrayIterator(array($fixtureOne)), 'emptyRelationArray', 0),
            array(new \ArrayIterator(array($fixtureOne)), 'emptyIterator', 0),
            array(new \ArrayIterator(array($fixtureOne)), 'relationArray', 2),
            array(new \ArrayIterator(array($fixtureOne, $fixtureTwo)), 'relationIterator', 5),
        );
    }
}
