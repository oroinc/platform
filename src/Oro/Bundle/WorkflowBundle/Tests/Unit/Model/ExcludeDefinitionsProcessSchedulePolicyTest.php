<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;
use Oro\Bundle\WorkflowBundle\Model\ExcludeDefinitionsProcessSchedulePolicy;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

class ExcludeDefinitionsProcessSchedulePolicyTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExcludeDefinitionsProcessSchedulePolicy */
    private $schedulePolicy;

    protected function setUp(): void
    {
        $this->schedulePolicy = new ExcludeDefinitionsProcessSchedulePolicy();
    }

    /**
     * @dataProvider isScheduleAllowedDataProvider
     */
    public function testIsScheduleAllowed(
        array $beforeEvents,
        array $afterEvents,
        array $expects
    ) {
        foreach ($beforeEvents as $event) {
            $this->schedulePolicy->onProcessHandleBefore($event);
        }

        foreach ($expects as $expectation) {
            $this->assertEquals(
                $expectation['allowed_before'],
                $this->schedulePolicy->isScheduleAllowed(
                    $expectation['trigger'],
                    $this->createMock(ProcessData::class)
                )
            );
        }

        foreach ($afterEvents as $event) {
            $this->schedulePolicy->onProcessHandleAfterFlush($event);
        }

        foreach ($expects as $expectation) {
            $this->assertEquals(
                $expectation['allowed_after'],
                $this->schedulePolicy->isScheduleAllowed(
                    $expectation['trigger'],
                    $this->createMock(ProcessData::class)
                )
            );
        }
    }

    public function isScheduleAllowedDataProvider(): array
    {
        return [
            'allowed when processes has no exclude definitions' => [
                'before_events' => [
                    $this->createProcessHandleEvent($foo = $this->getMockProcessTrigger('foo')),
                ],
                'after_events' => [
                    $this->createProcessHandleEvent($foo),
                ],
                'expects' => [
                    [
                        'trigger' => $foo,
                        'allowed_before' => true,
                        'allowed_after' => true,
                    ]
                ],
            ],
            'allowed when exclude definition not match' => [
                'before_events' => [
                    $this->createProcessHandleEvent($foo = $this->getMockProcessTrigger('foo', ['bar'])),
                ],
                'after_events' => [
                    $this->createProcessHandleEvent($foo),
                ],
                'expects' => [
                    [
                        'trigger' => $foo,
                        'allowed_before' => true,
                        'allowed_after' => true,
                    ]
                ],
            ],
            'not allowed when self excluded' => [
                'before_events' => [
                    $this->createProcessHandleEvent($foo = $this->getMockProcessTrigger('foo', ['foo'])),
                ],
                'after_events' => [
                    $this->createProcessHandleEvent($foo),
                ],
                'expects' => [
                    [
                        'trigger' => $foo,
                        'allowed_before' => false,
                        'allowed_after' => true,
                    ]
                ],
            ],
            'not allowed when excluded by other process' => [
                'before_events' => [
                    $this->createProcessHandleEvent($foo = $this->getMockProcessTrigger('foo', ['bar'])),
                    $this->createProcessHandleEvent($bar = $this->getMockProcessTrigger('bar')),
                ],
                'after_events' => [
                    $this->createProcessHandleEvent($foo),
                    $this->createProcessHandleEvent($bar),
                ],
                'expects' => [
                    [
                        'trigger' => $foo,
                        'allowed_before' => true,
                        'allowed_after' => true,
                    ],
                    [
                        'trigger' => $bar,
                        'allowed_before' => false,
                        'allowed_after' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @return ProcessHandleEvent
     */
    private function createProcessHandleEvent(ProcessTrigger $processTrigger)
    {
        return new ProcessHandleEvent($processTrigger, $this->createMock(ProcessData::class));
    }

    /**
     * @param string $processDefinitionName
     * @param array $excludeDefinitions
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockProcessTrigger($processDefinitionName, array $excludeDefinitions = array())
    {
        $processDefinition = $this->createMock(ProcessDefinition::class);
        $processDefinition->expects($this->any())
            ->method('getName')
            ->willReturn($processDefinitionName);
        $processDefinition->expects($this->any())
            ->method('getExcludeDefinitions')
            ->willReturn($excludeDefinitions);

        $processTrigger = $this->createMock(ProcessTrigger::class);
        $processTrigger->expects($this->any())
            ->method('getDefinition')
            ->willReturn($processDefinition);

        return $processTrigger;
    }
}
