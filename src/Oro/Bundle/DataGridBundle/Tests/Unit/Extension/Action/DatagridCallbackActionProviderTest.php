<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Action\DatagridCallbackActionProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Stub\CallableStub;

class DatagridCallbackActionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridCallbackActionProvider */
    protected $provider;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    protected $config;

    protected function setUp()
    {
        $this->provider = new DatagridCallbackActionProvider();

        $this->config = $this->createMock(DatagridConfiguration::class);
    }

    public function testHasActions()
    {
        $this->assertTrue($this->provider->hasActions(DatagridConfiguration::create([])));
    }

    public function testApplyActionsNothingToDo()
    {
        $this->config->expects($this->once())->method('offsetGetOr')->with('action_configuration')->willReturn(null);

        $this->provider->applyActions($this->config);
    }

    public function testApplyActionsGotNonCallable()
    {
        $this->config->expects($this->once())
            ->method('offsetGetOr')
            ->with('action_configuration')
            ->willReturn(['come data']);

        $this->provider->applyActions($this->config);
    }

    public function testApplyActionsGotCallable()
    {
        $callable = $this->createMock(CallableStub::class);

        $this->config->expects($this->exactly(3))
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

        $propertyConfigExpected = [
            'type' => 'callback',
            'callable' => $callable,
            'frontend_type' => 'row_array'
        ];

        $this->config->expects($this->once())->method('offsetAddToArrayByPath')->with(
            '[properties][action_configuration]',
            $this->callback(function ($argument) use ($propertyConfigExpected, $resultRecord) {
                $this->assertArraySubset(
                    [
                        'type' => 'callback',
                        'frontend_type' => 'row_array'
                    ],
                    $argument
                );
                $this->assertArrayHasKey('callable', $argument);

                $this->assertEquals(['an array'], $argument['callable']($resultRecord));
                $this->assertEquals([], $argument['callable']($resultRecord));
                return true;
            })
        );

        $this->provider->applyActions($this->config);
    }
}
