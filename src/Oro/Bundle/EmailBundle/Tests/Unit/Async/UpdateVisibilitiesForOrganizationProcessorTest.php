<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailVisibilitiesForOrganizationTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateVisibilitiesForOrganizationTopic;
use Oro\Bundle\EmailBundle\Async\UpdateVisibilitiesForOrganizationProcessor;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class UpdateVisibilitiesForOrganizationProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    /** @var EmailAddressVisibilityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressVisibilityManager;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var UpdateVisibilitiesForOrganizationProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);
        $this->jobRunner = $this->createMock(JobRunner::class);

        $this->processor = new UpdateVisibilitiesForOrganizationProcessor(
            $this->emailAddressVisibilityManager,
            self::getMessageProducer(),
            $this->jobRunner
        );
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        return $message;
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [UpdateVisibilitiesForOrganizationTopic::getName()],
            UpdateVisibilitiesForOrganizationProcessor::getSubscribedTopics()
        );
    }

    public function testProcess(): void
    {
        $jobId = 123;
        $organizationId = 1;

        $message = $this->getMessage(['jobId' => $jobId, 'organizationId' => $organizationId]);

        $this->emailAddressVisibilityManager->expects(self::once())
            ->method('updateEmailAddressVisibilities')
            ->with($organizationId);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $runCallback) {
                return $runCallback($this->jobRunner, new Job());
            });

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);

        self::assertMessageSent(
            UpdateEmailVisibilitiesForOrganizationTopic::getName(),
            ['organizationId' => $organizationId]
        );
    }
}
