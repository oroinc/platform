<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\EventListener;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Event\EventInterface;
use Oro\Bundle\BatchBundle\Event\JobExecutionEvent;
use Oro\Bundle\BatchBundle\EventListener\SetJobExecutionLogFileSubscriber;
use Oro\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SetJobExecutionLogFileSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var BatchLogHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var SetJobExecutionLogFileSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(BatchLogHandler::class);

        $this->subscriber = new SetJobExecutionLogFileSubscriber($this->logger);
    }

    public function testIsAnEventSubscriber(): void
    {
        self::assertInstanceOf(EventSubscriberInterface::class, $this->subscriber);
    }

    public function testSubscribedEvents(): void
    {
        self::assertEquals(
            [
                EventInterface::BEFORE_JOB_EXECUTION => 'setJobExecutionLogFile',
            ],
            SetJobExecutionLogFileSubscriber::getSubscribedEvents()
        );
    }

    public function testSetJobExecutionLogFile(): void
    {
        $this->logger
            ->expects(self::any())
            ->method('getFileName')
            ->willReturn('/tmp/foo.log');

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects(self::once())
            ->method('setLogFile')
            ->with('/tmp/foo.log');

        $event = new JobExecutionEvent($jobExecution);
        $this->subscriber->setJobExecutionLogFile($event);
    }
}
