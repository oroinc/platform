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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $args;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $options;

    /**
     * @var array
     */
    private $optionsArray;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $iteratedResult;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $firstResultRecord;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $secondResultRecord;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $firstEntity;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $secondEntity;

    /**
     * @var int
     */
    private $firstResultRecordId;

    /**
     * @var int
     */
    private $secondResultRecordId;

    protected function setUp()
    {
        $this->initMockObjects();
        $this->setUpMockObjects();

        $this->target = new MergeMassActionHandler($this->doctrineHelper);
    }

    private function initMockObjects()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->iteratedResult = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->firstResultRecord = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\ResultRecord')
            ->disableOriginalConstructor()
            ->getMock();

        $this->secondResultRecord = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\ResultRecord')
            ->disableOriginalConstructor()
            ->getMock();

        $this->args = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->options = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
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

        $this->firstEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($this->firstResultRecordId));

        $this->secondEntity->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($this->secondResultRecordId));

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->withAnyParameters()
            ->will($this->returnValue('id'));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntitiesByIds')
            ->withAnyParameters()
            ->will($this->returnValue(array($this->firstEntity, $this->secondEntity)));

        $options = & $this->optionsArray;
        $this->options->expects($this->any())
            ->method('toArray')
            ->withAnyParameters()
            ->will(
                $this->returnCallback(
                    function () use (&$options) {
                        return $options;
                    }
                )
            );

        $this->optionsArray = array(
            'entity_name'       => 'test_entity',
            'max_element_count' => 5
        );

        $fakeMassAction = $this->getMockBuilder(
            'Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $fakeMassAction->expects($this->any())
            ->method('getOptions')
            ->will($this->returnValue($this->options));

        $this->args->expects($this->any())
            ->method('getMassAction')
            ->withAnyParameters()
            ->will($this->returnValue($fakeMassAction));

        $this->args->expects($this->any())
            ->method('getResults')
            ->withAnyParameters()
            ->will($this->returnValue($this->iteratedResult));
    }

    public function testMethodDoesNotThrowAnExceptionIfAllDataIsCorrect()
    {
        $this->setIteratedResultMock();

        $result = $this->target->handle($this->args);

        $this->assertInstanceOf(
            'Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse',
            $result
        );

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

    public function testHandleShouldCallDoctrineHelperMethodGetEntitiesByIdsWithCorrectData()
    {
        $expectedIdFirst = rand();
        $expectedIdSecond = rand();

        $this->firstResultRecord->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($expectedIdFirst));

        $this->secondResultRecord->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($expectedIdSecond));

        $this->setIteratedResultMock();
        $this->optionsArray['entity_name'] = 'AccountTestEntityName';
        $callback = function ($param) use ($expectedIdFirst, $expectedIdSecond) {
            return $param[0] == $expectedIdFirst && $param[1] == $expectedIdSecond;
        };

        $this->doctrineHelper->expects($this->once())
            ->method('getEntitiesByIds')
            ->with(
                $this->equalTo('AccountTestEntityName'),
                $this->callback($callback)
            );

        $this->target->handle($this->args);
    }

    private function setIteratedResultMock()
    {
        $this->iteratedResult->expects($this->at(0))
            ->method('rewind');

        $this->firstResultRecord->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($this->firstResultRecordId));

        $this->secondResultRecord->expects($this->any())
            ->method('getValue')
            ->will(
                $this->returnValue($this->secondResultRecordId)
            );

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
}
