<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\DatagridCallbackActionProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\CallableStub;
use PHPUnit\Framework\TestCase;

class DatagridCallbackActionProviderTest extends TestCase
{
    private DatagridCallbackActionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new DatagridCallbackActionProvider();
    }

    public function testHasActions(): void
    {
        $this->assertTrue($this->provider->hasActions(DatagridConfiguration::create([])));
    }

    public function testApplyActionsNothingToDo(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('offsetGetOr')
            ->with('action_configuration')
            ->willReturn(null);

        $this->provider->applyActions($config);
    }

    public function testApplyActionsGotNonCallable(): void
    {
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('offsetGetOr')
            ->with('action_configuration')
            ->willReturn(['come data']);

        $this->provider->applyActions($config);
    }

    public function testApplyActionsGotCallable(): void
    {
        $callable = $this->createMock(CallableStub::class);

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->exactly(3))
            ->method('offsetGetOr')
            ->withConsecutive(
                ['action_configuration'],
                ['actions', []],
                ['actions', []]
            )
            ->willReturnOnConsecutiveCalls(
                $callable,
                ['actions cfg1'],
                ['actions cfg2']
            );

        $resultRecord = $this->createMock(ResultRecordInterface::class);

        $callable->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$resultRecord, ['actions cfg1']],
                [$resultRecord, ['actions cfg2']]
            )
            ->willReturnOnConsecutiveCalls(['an array'], 'not an array');

        $config->expects($this->once())
            ->method('offsetAddToArrayByPath')
            ->with(
                '[properties][action_configuration]',
                $this->callback(function ($argument) use ($resultRecord) {
                    $this->assertSame('callback', $argument['type']);
                    $this->assertSame('row_array', $argument['frontend_type']);
                    $this->assertArrayHasKey('callable', $argument);
                    $this->assertEquals(['an array'], $argument['callable']($resultRecord));
                    $this->assertEquals([], $argument['callable']($resultRecord));

                    return true;
                })
            );

        $this->provider->applyActions($config);
    }
}
