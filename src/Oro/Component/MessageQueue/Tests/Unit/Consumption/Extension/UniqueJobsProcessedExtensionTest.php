<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\UniqueJobsProcessedExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class UniqueJobsProcessedExtensionTest extends \PHPUnit\Framework\TestCase
{
    private MockObject $jobManager;

    private Context $context;

    protected function setUp(): void
    {
        $this->jobManager = $this->createMock(JobManager::class);
        $this->context = $this->createContext();
    }

    public function testThatConsumerIsInterruptedWhenUniqueJobsAreProcessed()
    {
        $this->jobManager->method('getUniqueJobs')->willReturn([]);

        $this->context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('Consumer has been stopped because all unique jobs have been processed');

        // guard
        $this->assertFalse($this->context->isExecutionInterrupted());

        // test
        $extension = new UniqueJobsProcessedExtension($this->jobManager);

        $extension->onIdle($this->context);
        $this->assertTrue($this->context->isExecutionInterrupted());
        $this->assertEquals('Unique jobs are processed.', $this->context->getInterruptedReason());
    }

    public function testThatConsumerIsNotInterruptedWhenUniqueJobsAreNotProcessed()
    {
        $this->jobManager->method('getUniqueJobs')->willReturn(['test_root_job']);

        $this->context->getLogger()->expects($this->never())->method('debug');

        // guard
        $this->assertFalse($this->context->isExecutionInterrupted());

        // test
        $extension = new UniqueJobsProcessedExtension($this->jobManager);

        $extension->onIdle($this->context);
        $this->assertFalse($this->context->isExecutionInterrupted());
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setMessageConsumer($this->createMock(MessageConsumerInterface::class));
        $context->setMessageProcessorName('sample_processor');

        return $context;
    }
}
