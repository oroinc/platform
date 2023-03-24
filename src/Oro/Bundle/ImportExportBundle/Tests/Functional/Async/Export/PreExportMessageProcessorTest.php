<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Component\MessageQueue\Client\Config as MessageQueueConfig;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @dbIsolationPerTest
 */
class PreExportMessageProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use JobsAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->setSecurityToken();
    }

    public function testProcess(): void
    {
        $messageBody = [
            'jobName' => 'job_name',
            'processorAlias' => 'alias',
            'outputFormat' => 'csv',
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'options' => [],
        ];
        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody($messageBody);
        $message->setProperties([
            MessageQueueConfig::PARAMETER_TOPIC_NAME => ExportTopic::getName()
        ]);

        $this->createRootJobMyMessage($message);

        $exportingEntityIds = [1, 2, 3];

        $exportHandler = $this->createMock(ExportHandler::class);
        $exportHandler->expects(self::once())
            ->method('getExportingEntityIds')
            ->with(
                'job_name',
                ProcessorRegistry::TYPE_EXPORT,
                'alias',
                []
            )
            ->willReturn($exportingEntityIds);

        self::getContainer()->set('oro_importexport.handler.export.stub', $exportHandler);

        $processor = self::getContainer()->get('oro_importexport.async.pre_export');

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);

        $sentMessageBody = self::getSentMessage(ExportTopic::getName());
        self::assertMessageSentWithPriority(ExportTopic::getName(), MessagePriority::LOW);
        self::assertNotEmpty($sentMessageBody['jobId']);

        // Checks that dependent job is created.
        $job = $this->getJobProcessor()->findJobById($sentMessageBody['jobId']);
        $rootJobId = $job->getRootJob()->getId();
        $dependentJobs = $this->getDependentJobsByJobId($sentMessageBody['jobId']);

        self::assertContainsEquals(
            [
                'topic' => PostExportTopic::getName(),
                'message' =>
                    [
                        'jobId' => $rootJobId,
                        'entity' => null,
                        'jobName' => 'job_name',
                        'exportType' => ProcessorRegistry::TYPE_EXPORT,
                        'outputFormat' => 'csv',
                        'recipientUserId' => $this->getCurrentUser()->getId(),
                    ],
                'priority' => null,
            ],
            $dependentJobs
        );
    }

    private function getCurrentUser(): User
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);
    }

    private function setSecurityToken(): void
    {
        $user = $this->getCurrentUser();
        $token = new UsernamePasswordToken($user, false, 'k', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
    }
}
