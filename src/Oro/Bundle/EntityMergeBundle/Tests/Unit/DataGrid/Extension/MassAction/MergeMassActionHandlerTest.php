<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DataGrid\Extension\MassAction;

use Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\MergeMassActionHandler;

class MergeMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeMassActionHandler $target
     */
    private $target;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeArgs
     */
    private $args;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $doctrineRegistry
     */
    private $dataProvider;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeOptions
     */
    private $options;

    private $optionsArray;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $fakeResult
     */
    private $iteratedResult;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $firstTestObject
     */
    private $firstResultRecord;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $secondTestObject
     */
    private $secondResultRecord;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $firstEntity
     */
    private $firstEntity;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $secondEntity
     */
    private $secondEntity;

    private $firstResultRecordId;

    private $secondResultRecordId;

    private function initMockObjects()
    {
        $this->dataProvider = $this->getMock(
            'Oro\Bundle\EntityMergeBundle\Data\EntityProvider',
            array(),
            array(),
            '',
            false
        );
        $this->iteratedResult = $this->getMock(
            'Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface',
            array(),
            array(),
            '',
            false
        );
        $this->firstResultRecord = $this->getMock(
            'Oro\Bundle\DataGridBundle\Datasource\ResultRecord',
            array(),
            array(),
            '',
            false
        );
        $this->secondResultRecord = $this->getMock(
            'Oro\Bundle\DataGridBundle\Datasource\ResultRecord',
            array(),
            array(),
            '',
            false
        );
        $this->args = $this->getMock(
            'Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs',
            array(),
            array(),
            '',
            false
        );

        $this->options = $this->getMock(
            'Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration',
            array(),
            array(),
            '',
            false
        );


    }

    protected function setUp()
    {
        $this->initMockObjects();

        $this->setUpMockObjects();

        $this->target = new MergeMassActionHandler($this->dataProvider);
    }


    private function setIteratedResultMock()
    {
        $this->iteratedResult->expects($this->at(0))
            ->method('rewind');

        $this->firstResultRecord->expects($this->any())->method('getValue')->will(
            $this->returnValue($this->firstResultRecordId)
        );
        $this->secondResultRecord->expects($this->any())->method('getValue')->will(
            $this->returnValue($this->secondResultRecordId)
        );
        // iteration 1
        $this->iteratedResult->expects($this->at(1))
            ->method('valid')
            ->will($this->returnValue(true));
        $this->iteratedResult->expects($this->at(2))
            ->method('current')
            ->will($this->returnValue($this->firstResultRecord));
        $this->iteratedResult->expects($this->at(3))
            ->method('next');
        $this->iteratedResult->expects($this->at(4))
            ->method('valid')
            ->will($this->returnValue(true));
        $this->iteratedResult->expects($this->at(5))
            ->method('current')
            ->will($this->returnValue($this->secondResultRecord));
    }

    /**
     * @return mixed
     */
    private function setUpMockObjects()
    {
        $this->firstEntity = $this->getMock('stdClass', array('getId'));
        $this->secondEntity = $this->getMock('stdClass', array('getId'));
        $this->firstResultRecordId = rand();
        $this->secondResultRecordId = rand();
        $this->firstEntity->expects($this->any())->method('getId')->will($this->returnValue($this->firstResultRecordId));
        $this->secondEntity->expects($this->any())->method('getId')->will(
            $this->returnValue($this->secondResultRecordId)
        );

        $this->dataProvider
            ->expects($this->any())
            ->method(
                'getEntityIdentifier'
            )
            ->withAnyParameters()
            ->will($this->returnValue('id'));
        $this->dataProvider
            ->expects($this->any())
            ->method(
                'getEntitiesByIds'
            )
            ->withAnyParameters()
            ->will($this->returnValue(array($this->firstEntity, $this->secondEntity)));

        $options = & $this->optionsArray;
        $this->options
            ->expects($this->any())
            ->method('toArray')
            ->withAnyParameters()
            ->will(
                $this->returnCallback(
                    function () use (&$options) {
                        return $options;
                    }
                )
            );
        $this->setCorrectOptions();

        $fakeMassAction = $this->getMock(
            'Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface',
            array(),
            array(),
            '',
            false
        );

        $fakeMassAction->expects($this->any())->method('getOptions')->will($this->returnValue($this->options));

        $this->args
            ->expects($this->any())
            ->method('getMassAction')
            ->withAnyParameters()
            ->will($this->returnValue($fakeMassAction));
        $this->args
            ->expects($this->any())
            ->method('getResults')
            ->withAnyParameters()
            ->will($this->returnValue($this->iteratedResult));
    }

    protected function setCorrectOptions()
    {
        $this->optionsArray = array(
            'entity_name' => 'test_entity',
            'max_element_count' => 5
        );
    }

    public function testMethodDoesNotThrowAnExceptionIfAllDataIsCorrect()
    {
        $this->setIteratedResultMock();

        $result = $this->target->handle($this->args);

        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse', $result);

        $result = $result->getOptions();
        $this->assertArrayHasKey('entities', $result);
        $this->assertEquals(2, count($result['entities']));
        $this->assertArrayHasKey('options', $result);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Entity name is missing.
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfEntityNameIsEmpty()
    {
        $this->optionsArray['entity_name'] = '';

        $this->target->handle($this->args);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "max_element_count" of "" mass action is invalid.
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfMaxElementCountIsEmpty()
    {
        $this->optionsArray = array('entity_name' => 'test_entity', 'max_element_count' => '');

        $this->target->handle($this->args);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Count of selected items less then 2
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfCountOfItemsToMergeLessThan2()
    {
        $this->target->handle($this->args);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Too many items selected
     */
    public function testHandleMustThrowInvalidArgumentExceptionIfCountOfItemsToMergeMoreThanMaxAllowedInConfig()
    {
        $this->optionsArray['max_element_count'] = 2;
        $this->setIteratedResultMock();
        $this->iteratedResult->expects($this->at(6))
            ->method('next');
        $this->iteratedResult->expects($this->at(7))
            ->method('valid')
            ->will($this->returnValue(true));
        $this->iteratedResult->expects($this->at(8))
            ->method('current')
            ->will($this->returnValue($this->secondResultRecord));


        $this->target->handle($this->args);
    }

    public function testHandleMustReturnRequestedEntitiesForMerge()
    {
        $this->setIteratedResultMock();
        $result = $this->target->handle($this->args);
        $actual = $result->getOption('entities');

        $firstActual = $actual[0];
        $secondActual = $actual[1];

        $this->assertEquals($this->firstResultRecordId, $firstActual->getId());
        $this->assertEquals($this->secondResultRecordId, $secondActual->getId());
    }

    public function testHandleShouldCallEntityProviderMethodGetEntitiesByIdsWithCorrectData()
    {
        $expectedIdFirst = rand();
        $expectedIdSecond = rand();
        $this->firstResultRecord->expects($this->any())->method('getValue')->will($this->returnValue($expectedIdFirst));
        $this->secondResultRecord->expects($this->any())->method('getValue')->will($this->returnValue($expectedIdSecond));

        $this->setIteratedResultMock();
        $this->optionsArray['entity_name'] = 'AccountTestEntityName';
        $callback = function ($param) use($expectedIdFirst, $expectedIdSecond) {
            return $param[0] == $expectedIdFirst && $param[1] == $expectedIdSecond;
        };

        $this->dataProvider->expects($this->once())->method('getEntitiesByIds')->with(
            $this->equalTo('AccountTestEntityName'),
            $this->callback($callback)
        );

        $this->target->handle($this->args);
    }
}
