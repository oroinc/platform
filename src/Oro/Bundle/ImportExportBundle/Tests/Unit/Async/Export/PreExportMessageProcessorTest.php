<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Async\Topics as ImportExportTopics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PreExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testShouldReturnSubscribedTopics()
    {
        self::assertEquals([Topics::PRE_EXPORT], PreExportMessageProcessor::getSubscribedTopics());
    }

    public function invalidMessageBodyProvider(): array
    {
        return [
            [
                'loggerMessage' => 'Got invalid message',
                'messageBody'   => ['processorAlias' => 'alias'],
            ],
            [
                'loggerMessage' => 'Got invalid message',
                'messageBody'   => ['jobName' => 'name'],
            ],
        ];
    }

    /**
     * @dataProvider invalidMessageBodyProvider
     */
    public function testShouldLogErrorAndRejectMessageIfMessageBodyInvalid(string $loggerMessage, array $messageBody)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('critical')
            ->with($loggerMessage);

        $processor = new PreExportMessageProcessor(
            $this->createMock(JobRunner::class),
            $this->createMock(MessageProducerInterface::class),
            $this->getTokenStorage(),
            $this->createMock(DependentJobService::class),
            $logger,
            $this->createMock(ExportHandler::class),
            100
        );

        $message = new TransportMessage();
        $message->setBody(JSON::encode($messageBody));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldSetOrganizationAndACKMessage()
    {
        $exportHandler = $this->createMock(ExportHandler::class);
        $jobUniqueName = 'oro_importexport.pre_export.test.user_1';
        $message = new TransportMessage();
        $message->setBody(JSON::encode([
            'jobName'        => 'test',
            'processor'      => ProcessorRegistry::TYPE_EXPORT,
            'processorAlias' => 'test',
            'organizationId' => 22,
        ]));
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects(self::once())
            ->method('runUnique')
            ->with($this->equalTo(123), $this->equalTo($jobUniqueName))
            ->willReturnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            });
        $jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with($jobUniqueName . '.chunk.1')
            ->willReturnCallback(function ($name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            });

        $dependentJobContext = $this->createMock(DependentJobContext::class);
        $dependentJobContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                ImportExportTopics::POST_EXPORT,
                [
                    'jobId' => 1,
                    'recipientUserId' => 1,
                    'jobName' => 'test',
                    'exportType' => 'export',
                    'outputFormat' => 'csv',
                    'entity' => 'Acme',
                    'notificationTemplate' => 'export_result',
                ]
            );

        $dependentJob = $this->createMock(DependentJobService::class);
        $dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($this->equalTo($job))
            ->willReturn($dependentJobContext);
        $dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($this->equalTo($dependentJobContext));

        $exportHandler->expects(self::once())
            ->method('getExportingEntityIds')
            ->willReturn([]);
        $exportHandler->expects(self::once())
            ->method('getEntityName')
            ->willReturn('Acme');

        $processor = new PreExportMessageProcessor(
            $jobRunner,
            self::getMessageProducer(),
            $this->getTokenStorage(),
            $dependentJob,
            $this->createMock(LoggerInterface::class),
            $exportHandler,
            100
        );

        $organization = $this->createMock(Organization::class);
        $organizationRepository = $this->createMock(OrganizationRepository::class);
        $organizationRepository->expects(self::once())
            ->method('find')
            ->with($this->equalTo(22))
            ->willReturn($organization);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with($this->equalTo(Organization::class))
            ->willReturn($organizationRepository);
        $processor->setDoctrineHelper($doctrineHelper);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            Topics::EXPORT,
            [
                'jobName'        => 'test',
                'processorAlias' => 'test',
                'outputFormat'   => 'csv',
                'organizationId' => '22',
                'exportType'     => 'export',
                'options'        => [],
                'processor'      => 'export',
                'entity'         => 'Acme',
                'jobId'          => 10
            ]
        );
        self::assertMessageSentWithPriority(Topics::EXPORT, MessagePriority::LOW);
    }

    private function createJob(int $id, Job $rootJob = null): Job
    {
        $job = new Job();
        $job->setId($id);
        if (null !== $rootJob) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }

    private function getTokenStorage(): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($this->getUser());

        $tokeStorage = $this->createMock(TokenStorageInterface::class);
        $tokeStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        return $tokeStorage;
    }

    private function getUser(): UserInterface
    {
        $user = $this->getMockBuilder(UserInterface::class)
            ->onlyMethods(get_class_methods(UserInterface::class))
            ->addMethods(['getId', 'getEmail'])
            ->getMock();
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $user->expects(self::any())
            ->method('getEmail');

        return $user;
    }
}
