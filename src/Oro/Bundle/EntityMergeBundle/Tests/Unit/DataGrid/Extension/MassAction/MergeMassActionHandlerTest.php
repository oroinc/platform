<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\MergeMassActionHandler;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class MergeMassActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MassActionHandlerArgs|\PHPUnit\Framework\MockObject\MockObject */
    private $args;

    /** @var array */
    private $optionsArray;

    /** @var IterableResultInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $iteratedResult;

    /** @var ResultRecord|\PHPUnit\Framework\MockObject\MockObject */
    private $firstResultRecord;

    /** @var ResultRecord|\PHPUnit\Framework\MockObject\MockObject */
    private $secondResultRecord;

    /** @var EntityStub */
    private $firstEntity;

    /** @var EntityStub */
    private $secondEntity;

    /** @var MergeMassActionHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->args = $this->createMock(MassActionHandlerArgs::class);
        $this->iteratedResult = $this->createMock(IterableResultInterface::class);
        $this->firstResultRecord = $this->createMock(ResultRecord::class);
        $this->secondResultRecord = $this->createMock(ResultRecord::class);

        $this->firstEntity = new EntityStub(1);
        $this->secondEntity = new EntityStub(2);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->withAnyParameters()
            ->willReturn('id');
        $this->doctrineHelper->expects($this->any())
            ->method('getEntitiesByIds')
            ->withAnyParameters()
            ->willReturn([$this->firstEntity, $this->secondEntity]);

        $actionConfig = $this->createMock(ActionConfiguration::class);
        $options = &$this->optionsArray;
        $actionConfig->expects($this->any())
            ->method('toArray')
            ->withAnyParameters()
            ->willReturnCallback(function () use (&$options) {
                return $options;
            });
        $this->optionsArray = [
            'entity_name'       => 'test_entity',
            'max_element_count' => 5
        ];

        $fakeMassAction = $this->createMock(MassActionInterface::class);
        $fakeMassAction->expects($this->any())
            ->method('getOptions')
            ->willReturn($actionConfig);

        $this->args->expects($this->any())
            ->method('getMassAction')
            ->withAnyParameters()
            ->willReturn($fakeMassAction);
        $this->args->expects($this->any())
            ->method('getResults')
            ->withAnyParameters()
            ->willReturn($this->iteratedResult);

        $this->handler = new MergeMassActionHandler($this->doctrineHelper);
    }

    public function testMethodDoesNotThrowAnExceptionIfAllDataIsCorrect()
    {
        $this->setIteratedResultMock();

        $result = $this->handler->handle($this->args);

        $this->assertInstanceOf(MassActionResponse::class, $result);

        $result = $result->getOptions();

        $this->assertArrayHasKey('entities', $result);
        $this->assertCount(2, $result['entities']);
        $this->assertArrayHasKey('options', $result);
    }

    public function testHandleMustThrowInvalidArgumentExceptionIfEntityNameIsEmpty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity name is missing.');

        $this->optionsArray['entity_name'] = '';

        $this->handler->handle($this->args);
    }

    public function testHandleMustReturnRequestedEntitiesForMerge()
    {
        $this->setIteratedResultMock();
        $result = $this->handler->handle($this->args);
        $actual = $result->getOption('entities');

        [$firstActual, $secondActual] = $actual;

        $this->assertEquals($this->firstEntity->getId(), $firstActual->getId());
        $this->assertEquals($this->secondEntity->getId(), $secondActual->getId());
    }

    public function testHandleShouldCallDoctrineHelperMethodGetEntitiesByIdsWithCorrectData()
    {
        $expectedIdFirst = 100;
        $expectedIdSecond = 200;

        $this->firstResultRecord->expects($this->any())
            ->method('getValue')
            ->willReturn($expectedIdFirst);

        $this->secondResultRecord->expects($this->any())
            ->method('getValue')
            ->willReturn($expectedIdSecond);

        $this->setIteratedResultMock();
        $this->optionsArray['entity_name'] = 'AccountTestEntityName';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntitiesByIds')
            ->with(
                'AccountTestEntityName',
                $this->callback(function ($param) use ($expectedIdFirst, $expectedIdSecond) {
                    return $param[0] === $expectedIdFirst && $param[1] === $expectedIdSecond;
                })
            );

        $this->handler->handle($this->args);
    }

    private function setIteratedResultMock(): void
    {
        $this->firstResultRecord->expects($this->any())
            ->method('getValue')
            ->willReturn($this->firstEntity->getId());

        $this->secondResultRecord->expects($this->any())
            ->method('getValue')
            ->willReturn($this->secondEntity->getId());

        $this->iteratedResult->expects($this->once())
            ->method('rewind');
        $this->iteratedResult->expects($this->exactly(3))
            ->method('valid')
            ->willReturnOnConsecutiveCalls(true, true, false);
        $this->iteratedResult->expects($this->exactly(2))
            ->method('current')
            ->willReturn($this->firstResultRecord, $this->secondResultRecord);
    }
}
