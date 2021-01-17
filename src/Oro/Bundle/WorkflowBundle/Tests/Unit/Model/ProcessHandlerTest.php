<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessEvents;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;
use Oro\Bundle\WorkflowBundle\Model\Process;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessFactory;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessFactory|MockObject */
    protected $factory;

    /** @var ProcessLogger|MockObject */
    protected $logger;

    /** @var EventDispatcherInterface|MockObject */
    protected $eventDispatcher;

    /** @var ProcessHandler */
    protected $handler;

    protected function setUp(): void
    {
        $this->factory = $this->getMockBuilder(ProcessFactory::class)->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder(ProcessLogger::class)->disableOriginalConstructor()->getMock();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new ProcessHandler($this->factory, $this->logger, $this->eventDispatcher);
    }

    public function testHandleTrigger()
    {
        $processData = new ProcessData([
            'data' => new \DateTime(),
            'old'  => ['label' => 'before'],
            'new'  => ['label' => 'after']
        ]);

        $processTrigger = $this->prepareHandleTrigger($processData);
        $this->handler->handleTrigger($processTrigger, $processData);
    }

    public function prepareHandleTrigger($processData)
    {
        $processDefinition = $this->createMock(ProcessDefinition::class);
        $processTrigger = $this->createMock(ProcessTrigger::class);
        $processTrigger->expects(static::once())->method('getDefinition')->willReturn($processDefinition);

        $process = $this->getMockBuilder(Process::class)->disableOriginalConstructor()->getMock();
        $process->expects(static::once())
            ->method('execute')
            ->with($processData)
            ->willReturn($processDefinition);

        $this->factory->expects(static::once())
            ->method('create')
            ->with($processDefinition)
            ->willReturn($process);
        $this->logger->expects(static::once())
            ->method('debug')
            ->with('Process executed', $processTrigger, $processData);

        $this->eventDispatcher->expects(static::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    static::callback(
                        function ($event) use ($processTrigger, $processData) {
                            static::assertInstanceOf(ProcessHandleEvent::class, $event);
                            /** @var ProcessHandleEvent $event */
                            static::assertSame($processTrigger, $event->getProcessTrigger());
                            static::assertSame($processData, $event->getProcessData());
                            return true;
                        }
                    ),
                    ProcessEvents::HANDLE_BEFORE
                ],
                [
                    static::callback(
                        function ($event) use ($processTrigger, $processData, $process) {
                            static::assertInstanceOf(ProcessHandleEvent::class, $event);
                            /** @var ProcessHandleEvent $event */
                            static::assertSame($processTrigger, $event->getProcessTrigger());
                            static::assertSame($processData, $event->getProcessData());
                            return true;
                        }
                    ),
                    ProcessEvents::HANDLE_AFTER
                ]
            );

        return $processTrigger;
    }

    /**
     * @dataProvider handleJobProvider
     */
    public function testHandleJob($data)
    {
        $processTrigger = $this->prepareHandleTrigger($data);

        $processJob = $this->createMock(ProcessJob::class);
        $processJob->expects(static::once())->method('getProcessTrigger')->willReturn($processTrigger);
        $processJob->expects(static::once())->method('getData')->willReturn($data);

        $this->handler->handleJob($processJob);
    }

    public function handleJobProvider()
    {
        $entity = new \DateTime();
        return [
            'event create or delete' => [
                'data' => new ProcessData([
                    'data' => $entity
                ])
            ],
            'event update' => [
                'data' => new ProcessData([
                    'data' => $entity,
                    'old'  => ['label' => 'before'],
                    'new'  => ['label' => 'after'],
                ])
            ],
        ];
    }

    public function testFinishTrigger()
    {
        $processTrigger = $this->createMock(ProcessTrigger::class);
        /** @var ProcessData|MockObject $processData */
        $processData = $this->getMockBuilder(ProcessData::class)->disableOriginalConstructor()->getMock();

        $this->eventDispatcher->expects(static::once())
            ->method('dispatch')
            ->with(
                static::callback(
                    function ($event) use ($processTrigger, $processData) {
                        static::assertInstanceOf(ProcessHandleEvent::class, $event);
                        /** @var ProcessHandleEvent $event */
                        static::assertSame($processTrigger, $event->getProcessTrigger());
                        static::assertSame($processData, $event->getProcessData());
                        return true;
                    }
                ),
                ProcessEvents::HANDLE_AFTER_FLUSH
            );

        $this->handler->finishTrigger($processTrigger, $processData);
    }

    public function testFinishJob()
    {
        $processTrigger = $this->createMock(ProcessTrigger::class);
        $processData = $this->getMockBuilder(ProcessData::class)->disableOriginalConstructor()->getMock();

        $processJob = $this->createMock(ProcessJob::class);
        $processJob->expects(static::once())->method('getProcessTrigger')->willReturn($processTrigger);
        $processJob->expects(static::once())->method('getData')->willReturn($processData);

        $this->eventDispatcher->expects(static::once())
            ->method('dispatch')
            ->with(
                static::callback(
                    function ($event) use ($processTrigger, $processData) {
                        static::assertInstanceOf(ProcessHandleEvent::class, $event);
                        static::assertSame($processTrigger, $event->getProcessTrigger());
                        static::assertSame($processData, $event->getProcessData());
                        return true;
                    }
                ),
                ProcessEvents::HANDLE_AFTER_FLUSH
            );

        $this->handler->finishJob($processJob);
    }
}
