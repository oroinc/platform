<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PreExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    private const JOB_NAME = 'oro_importexport.pre_export.test.user_1';

    public function testShouldReturnSubscribedTopics(): void
    {
        self::assertEquals([PreExportTopic::getName()], PreExportMessageProcessor::getSubscribedTopics());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldSetOrganizationAndACKMessage(): void
    {
        $exportHandler = $this->createMock(ExportHandler::class);
        $jobUniqueName = self::JOB_NAME;
        $message = new TransportMessage();
        $message->setBody([
            'jobName' => 'test',
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'processorAlias' => 'test',
            'organizationId' => 22,
            'outputFormat' => 'csv',
            'options' => [],
        ]);
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($jobRunner, $childJob) {
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
                PostExportTopic::getName(),
                [
                    'jobId' => 1,
                    'recipientUserId' => 1,
                    'jobName' => 'test',
                    'exportType' => 'export',
                    'outputFormat' => 'csv',
                    'entity' => 'Acme',
                ]
            );

        $dependentJob = $this->createMock(DependentJobService::class);
        $dependentJob->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $dependentJob->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

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
            ->with(22)
            ->willReturn($organization);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(Organization::class)
            ->willReturn($organizationRepository);
        $processor->setDoctrineHelper($doctrineHelper);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            ExportTopic::getName(),
            [
                'jobName'        => 'test',
                'processorAlias' => 'test',
                'outputFormat'   => 'csv',
                'organizationId' => '22',
                'exportType'     => ProcessorRegistry::TYPE_EXPORT,
                'options'        => [],
                'entity'         => 'Acme',
                'jobId'          => 10
            ]
        );
        self::assertMessageSentWithPriority(ExportTopic::getName(), MessagePriority::LOW);
    }

    private function createJob(int $id, Job $rootJob = null): Job
    {
        $job = new Job();
        $job->setId($id);
        $job->setName(self::JOB_NAME);
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
            ->willReturn(new UserStub(1));

        $tokeStorage = $this->createMock(TokenStorageInterface::class);
        $tokeStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        return $tokeStorage;
    }
}
