<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Async\UpdateVisibilitiesForOrganizationProcessor;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Logger\BufferingLogger;

class UpdateVisibilitiesForOrganizationProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    /** @var EmailAddressVisibilityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressVisibilityManager;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var BufferingLogger */
    private $logger;

    /** @var UpdateVisibilitiesForOrganizationProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->emailAddressVisibilityManager = $this->createMock(EmailAddressVisibilityManager::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->logger = new BufferingLogger();

        $this->processor = new UpdateVisibilitiesForOrganizationProcessor(
            $this->emailAddressVisibilityManager,
            self::getMessageProducer(),
            $this->jobRunner,
            $this->logger
        );
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($body));

        return $message;
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [Topics::UPDATE_VISIBILITIES_FOR_ORGANIZATION],
            UpdateVisibilitiesForOrganizationProcessor::getSubscribedTopics()
        );
    }

    /**
     * @dataProvider invalidMessageDataProvider
     */
    public function testProcessWhenMessageIsInvalid(array $messageBody): void
    {
        $message = $this->getMessage($messageBody);

        $result = $this->processor->process(
            $message,
            $this->createMock(SessionInterface::class)
        );
        self::assertEquals(MessageProcessorInterface::REJECT, $result);

        self::assertEquals(
            [
                ['critical', 'Got invalid message.', []]
            ],
            $this->logger->cleanLogs()
        );
    }

    public function invalidMessageDataProvider(): array
    {
        return [
            [[]],
            [['jobId' => 1]],
            [['organizationId' => 1]],
        ];
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
            Topics::UPDATE_EMAIL_VISIBILITIES_FOR_ORGANIZATION,
            ['organizationId' => $organizationId]
        );

        self::assertEmpty($this->logger->cleanLogs());
    }
}
