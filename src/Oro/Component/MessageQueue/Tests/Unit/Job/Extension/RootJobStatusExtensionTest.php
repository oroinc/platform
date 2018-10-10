<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job\Extension;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Extension\RootJobStatusExtension;
use Oro\Component\MessageQueue\Job\Job;

class RootJobStatusExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var RootJobStatusExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->extension = new RootJobStatusExtension($this->producer);
    }

    public function testOnPreRunUnique()
    {
        $job = new Job();
        $job->setId('uniqueJobId');
        $message = new Message(
            ['jobId' => 'uniqueJobId', 'calculateProgress' => true],
            'oro.message_queue.client.high_message_priority'
        );

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with('oro.message_queue.job.calculate_root_job_status', $message);

        $this->extension->onPreRunUnique($job);
    }

    public function testOnPostRunUnique()
    {
        $job = new Job();
        $job->setId('uniqueJobId');
        $message = new Message(
            ['jobId' => 'uniqueJobId', 'calculateProgress' => true],
            'oro.message_queue.client.high_message_priority'
        );

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with('oro.message_queue.job.calculate_root_job_status', $message);

        $this->extension->onPostRunUnique($job, '');
    }

    public function testOnPreRunDelayed()
    {
        $job = new Job();
        $job->setId('delayedJobId');
        $message = new Message(
            ['jobId' => 'delayedJobId', 'calculateProgress' => true],
            'oro.message_queue.client.high_message_priority'
        );

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with('oro.message_queue.job.calculate_root_job_status', $message);

        $this->extension->onPreRunDelayed($job);
    }

    public function testOnPostRunDelayed()
    {
        $job = new Job();
        $job->setId('delayedJobId');
        $message = new Message(
            ['jobId' => 'delayedJobId', 'calculateProgress' => true],
            'oro.message_queue.client.high_message_priority'
        );

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with('oro.message_queue.job.calculate_root_job_status', $message);

        $this->extension->onPostRunUnique($job, '');
    }

    public function testOnCancel()
    {
        $job = new Job();
        $job->setId('jobId');
        $message = new Message(
            ['jobId' => 'jobId', 'calculateProgress' => true],
            'oro.message_queue.client.high_message_priority'
        );

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with('oro.message_queue.job.calculate_root_job_status', $message);

        $this->extension->onCancel($job);
    }

    public function testOnError()
    {
        $job = new Job();
        $job->setId('jobId');
        $message = new Message(
            ['jobId' => 'jobId', 'calculateProgress' => true],
            'oro.message_queue.client.high_message_priority'
        );

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with('oro.message_queue.job.calculate_root_job_status', $message);

        $this->extension->onError($job);
    }
}
