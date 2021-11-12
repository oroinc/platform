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
    private $factory;

    /** @var ProcessLogger|MockObject */
    private $logger;

    /** @var EventDispatcherInterface|MockObject */
    private $eventDispatcher;

    /** @var ProcessHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(ProcessFactory::class);
        $this->logger = $this->createMock(ProcessLogger::class);
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
        $processTrigger->expects(self::once())
            ->method('getDefinition')
            ->willReturn($processDefinition);

        $process = $this->createMock(Process::class);
        $process->expects(self::once())
            ->method('execute')
            ->with($processData)
            ->willReturn($processDefinition);

        $this->factory->expects(self::once())
            ->method('create')
            ->with($processDefinition)
            ->willReturn($process);
        $this->logger->expects(self::once())
            ->method('debug')
            ->with('Process executed', $processTrigger, $processData);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::callback(function ($event) use ($processTrigger, $processData) {
                        self::assertInstanceOf(ProcessHandleEvent::class, $event);
                        /** @var ProcessHandleEvent $event */
                        self::assertSame($processTrigger, $event->getProcessTrigger());
                        self::assertSame($processData, $event->getProcessData());

                        return true;
                    }),
                    ProcessEvents::HANDLE_BEFORE
                ],
                [
                    self::callback(function ($event) use ($processTrigger, $processData, $process) {
                        self::assertInstanceOf(ProcessHandleEvent::class, $event);
                        /** @var ProcessHandleEvent $event */
                        self::assertSame($processTrigger, $event->getProcessTrigger());
                        self::assertSame($processData, $event->getProcessData());

                        return true;
                    }),
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
        $processJob->expects(self::once())
            ->method('getProcessTrigger')
            ->willReturn($processTrigger);
        $processJob->expects(self::once())
            ->method('getData')
            ->willReturn($data);

        $this->handler->handleJob($processJob);
    }

    public function handleJobProvider(): array
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
        $processData = $this->createMock(ProcessData::class);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(function ($event) use ($processTrigger, $processData) {
                    self::assertInstanceOf(ProcessHandleEvent::class, $event);
                    /** @var ProcessHandleEvent $event */
                    self::assertSame($processTrigger, $event->getProcessTrigger());
                    self::assertSame($processData, $event->getProcessData());

                    return true;
                }),
                ProcessEvents::HANDLE_AFTER_FLUSH
            );

        $this->handler->finishTrigger($processTrigger, $processData);
    }

    public function testFinishJob()
    {
        $processTrigger = $this->createMock(ProcessTrigger::class);
        $processData = $this->createMock(ProcessData::class);

        $processJob = $this->createMock(ProcessJob::class);
        $processJob->expects(self::once())
            ->method('getProcessTrigger')
            ->willReturn($processTrigger);
        $processJob->expects(self::once())
            ->method('getData')
            ->willReturn($processData);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(function ($event) use ($processTrigger, $processData) {
                    self::assertInstanceOf(ProcessHandleEvent::class, $event);
                    self::assertSame($processTrigger, $event->getProcessTrigger());
                    self::assertSame($processData, $event->getProcessData());

                    return true;
                }),
                ProcessEvents::HANDLE_AFTER_FLUSH
            );

        $this->handler->finishJob($processJob);
    }
}
