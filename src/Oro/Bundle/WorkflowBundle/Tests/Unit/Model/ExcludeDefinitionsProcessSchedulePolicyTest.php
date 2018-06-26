<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;
use Oro\Bundle\WorkflowBundle\Model\ExcludeDefinitionsProcessSchedulePolicy;

class ExcludeDefinitionsProcessSchedulePolicyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExcludeDefinitionsProcessSchedulePolicy
     */
    protected $schedulePolicy;

    protected function setUp()
    {
        $this->schedulePolicy = new ExcludeDefinitionsProcessSchedulePolicy();
    }

    /**
     * @dataProvider isScheduleAllowedDataProvider
     *
     * @param array $beforeEvents
     * @param array $afterEvents
     * @param array $expects
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
                $this->schedulePolicy->isScheduleAllowed($expectation['trigger'], $this->getMockProcessData())
            );
        }

        foreach ($afterEvents as $event) {
            $this->schedulePolicy->onProcessHandleAfterFlush($event);
        }


        foreach ($expects as $expectation) {
            $this->assertEquals(
                $expectation['allowed_after'],
                $this->schedulePolicy->isScheduleAllowed($expectation['trigger'], $this->getMockProcessData())
            );
        }
    }

    /**
     * @return array
     */
    public function isScheduleAllowedDataProvider()
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
    protected function createProcessHandleEvent(ProcessTrigger $processTrigger)
    {
        return new ProcessHandleEvent($processTrigger, $this->getMockProcessData());
    }

    /**
     * @param string $processDefinitionName
     * @param array $excludeDefinitions
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockProcessTrigger($processDefinitionName, array $excludeDefinitions = array())
    {
        $processDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $processDefinition->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($processDefinitionName));
        $processDefinition->expects($this->any())
            ->method('getExcludeDefinitions')
            ->will($this->returnValue($excludeDefinitions));

        $processTrigger = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger')
            ->disableOriginalConstructor()
            ->getMock();
        $processTrigger->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue($processDefinition));

        return $processTrigger;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockProcessData()
    {
        return $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessData')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockProcess()
    {
        return $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Process')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
