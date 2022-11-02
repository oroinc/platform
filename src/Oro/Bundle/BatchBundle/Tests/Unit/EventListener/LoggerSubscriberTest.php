<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Event\EventInterface;
use Oro\Bundle\BatchBundle\Event\InvalidItemEvent;
use Oro\Bundle\BatchBundle\Event\JobExecutionEvent;
use Oro\Bundle\BatchBundle\Event\StepExecutionEvent;
use Oro\Bundle\BatchBundle\EventListener\LoggerSubscriber;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoggerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var BufferingLogger */
    private $logger;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var LoggerSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->logger = new BufferingLogger();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->subscriber = new LoggerSubscriber($this->logger, $this->translator);
    }

    public function testIsAnEventSubscriber(): void
    {
        self::assertInstanceOf(EventSubscriberInterface::class, $this->subscriber);
    }

    public function testSubscribedEvents(): void
    {
        self::assertEquals(
            [
                EventInterface::BEFORE_JOB_EXECUTION => 'beforeJobExecution',
                EventInterface::JOB_EXECUTION_STOPPED => 'jobExecutionStopped',
                EventInterface::JOB_EXECUTION_INTERRUPTED => 'jobExecutionInterrupted',
                EventInterface::JOB_EXECUTION_FATAL_ERROR => 'jobExecutionFatalError',
                EventInterface::BEFORE_JOB_STATUS_UPGRADE => 'beforeJobStatusUpgrade',
                EventInterface::BEFORE_STEP_EXECUTION => 'beforeStepExecution',
                EventInterface::STEP_EXECUTION_SUCCEEDED => 'stepExecutionSucceeded',
                EventInterface::STEP_EXECUTION_INTERRUPTED => 'stepExecutionInterrupted',
                EventInterface::STEP_EXECUTION_ERRORED => 'stepExecutionErrored',
                EventInterface::STEP_EXECUTION_COMPLETED => 'stepExecutionCompleted',
                EventInterface::INVALID_ITEM => 'invalidItem',
            ],
            LoggerSubscriber::getSubscribedEvents()
        );
    }

    public function testBeforeJobExecution(): void
    {
        $event = new JobExecutionEvent(new JobExecution());
        $this->subscriber->beforeJobExecution($event);

        self::assertEquals(
            [['debug', sprintf('Job execution starting: %s', $event->getJobExecution()), []]],
            $this->logger->cleanLogs()
        );
    }

    public function testJobExecutionStopped(): void
    {
        $event = new JobExecutionEvent($this->createMock(JobExecution::class));
        $this->subscriber->jobExecutionStopped($event);

        self::assertEquals(
            [['debug', sprintf('Job execution was stopped: %s', $event->getJobExecution()), []]],
            $this->logger->cleanLogs()
        );
    }

    public function testJobExecutionInterrupted(): void
    {
        $event = new JobExecutionEvent($this->createMock(JobExecution::class));
        $this->subscriber->jobExecutionInterrupted($event);

        self::assertEquals(
            [
                ['info', sprintf('Encountered interruption executing job: %s', $event->getJobExecution()), []],
                ['debug', 'Full exception', ['exception', null]],
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testJobExecutionFatalError(): void
    {
        $event = new JobExecutionEvent($this->createMock(JobExecution::class));
        $this->subscriber->jobExecutionFatalError($event);

        self::assertEquals(
            [['error', 'Encountered fatal error executing job', ['exception', null]]],
            $this->logger->cleanLogs()
        );
    }

    public function testBeforeJobStatusUpgrade(): void
    {
        $event = new JobExecutionEvent($this->createMock(JobExecution::class));
        $this->subscriber->beforeJobStatusUpgrade($event);

        self::assertEquals(
            [['debug', sprintf('Upgrading JobExecution status: %s', $event->getJobExecution()), []]],
            $this->logger->cleanLogs()
        );
    }

    public function testBeforeStepExecution(): void
    {
        $stepExecution = new StepExecution('sample_step', new JobExecution());
        $event = new StepExecutionEvent($stepExecution);
        $this->subscriber->beforeStepExecution($event);

        self::assertEquals(
            [['info', sprintf('Step execution starting: %s', $event->getStepExecution()), []]],
            $this->logger->cleanLogs()
        );
    }

    public function testStepExecutionSucceeded(): void
    {
        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $event = new StepExecutionEvent($stepExecution);

        $this->subscriber->stepExecutionSucceeded($event);

        self::assertEquals([['debug', 'Step execution success: id= 1', []]], $this->logger->cleanLogs());
    }

    public function testStepExecutionInterrupted(): void
    {
        $stepExecution = new StepExecution('sample_step', new JobExecution());
        $event = new StepExecutionEvent($stepExecution);
        $this->subscriber->stepExecutionInterrupted($event);

        self::assertEquals(
            [
                [
                    'info',
                    sprintf(
                        'Encountered interruption executing step: %s',
                        $stepExecution->getFailureExceptionMessages()
                    ),
                    [],
                ],
                ['debug', 'Full exception', ['exception', []]],
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testStepExecutionErrored(): void
    {
        $this->translator
            ->expects(self::any())
            ->method('trans')
            ->willReturnMap(
                [
                    ['foo is wrong', ['foo' => 'Item1'], 'messages', 'en', 'Item1 is wrong'],
                    ['bar is wrong', ['bar' => 'Item2'], 'messages', 'en', 'Item2 is wrong'],
                ]
            );

        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects(self::any())
            ->method('getFailureExceptions')
            ->willReturn(
                [
                    [
                        'message' => 'foo is wrong',
                        'messageParameters' => [
                            'foo' => 'Item1',
                        ],
                    ],
                    [
                        'message' => 'bar is wrong',
                        'messageParameters' => [
                            'bar' => 'Item2',
                        ],
                    ],
                ]
            );

        $event = new StepExecutionEvent($stepExecution);
        $this->subscriber->stepExecutionErrored($event);

        self::assertEquals(
            [['error', 'Encountered an error executing the step: Item1 is wrong, Item2 is wrong', []]],
            $this->logger->cleanLogs()
        );
    }

    public function testStepExecutionCompleted(): void
    {
        $stepExecution = new StepExecution('sample_step', new JobExecution());
        $event = new StepExecutionEvent($stepExecution);
        $this->subscriber->stepExecutionCompleted($event);

        self::assertEquals(
            [['debug', sprintf('Step execution complete: %s', $event->getStepExecution()), []]],
            $this->logger->cleanLogs()
        );
    }

    public function testInvalidItemExecution(): void
    {
        $this->translator
            ->expects(self::any())
            ->method('trans')
            ->with('batch.invalid_item_reason', ['item' => 'foobar'], 'messages', 'en')
            ->willReturn('This is a valid reason.');

        $invalidItemEvent = new InvalidItemEvent(
            ItemReaderInterface::class,
            'batch.invalid_item_reason',
            ['item' => 'foobar'],
            ['foo' => 'bar']
        );
        $this->subscriber->invalidItem($invalidItemEvent);

        self::assertEquals(
            [
                [
                    'warning',
                    'The ' . ItemReaderInterface::class . ' was unable to handle the following item: ' .
                    '[foo => bar] (REASON: This is a valid reason.)',
                    [],
                ],
            ],
            $this->logger->cleanLogs()
        );
    }
}
