<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PreExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::PRE_EXPORT], PreExportMessageProcessor::getSubscribedTopics());
    }

    public function invalidMessageBodyProvider()
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
     *
     * @param string $loggerMessage
     * @param array  $messageBody
     */
    public function testShouldLogErrorAndRejectMessageIfMessageBodyInvalid($loggerMessage, array $messageBody)
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($loggerMessage);

        $processor = new PreExportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createTokenStorageMock(),
            $this->createDependentJobMock(),
            $logger,
            100
        );

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSetOrganizationAndACKMessage()
    {
        $jobUniqueName = 'oro_importexport.pre_export.test.user_1';
        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName'        => 'test',
            'processorAlias' => 'test',
            'organizationId' => 22,
        ]));
        $message->setMessageId(123);

        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($this->equalTo(123), $this->equalTo($jobUniqueName))
            ->will($this->returnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            }));
        $jobRunner->expects($this->once())
            ->method('createDelayed')
            ->with($jobUniqueName . '.chunk.1')
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner, $childJob) {
                return $callback($jobRunner, $childJob);
            }));

        $dependentJobContext = $this->createDependentJobContextMock();
        $dependentJobContext
            ->expects($this->once())
            ->method('addDependentJob');

        $dependentJob = $this->createDependentJobMock();
        $dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($this->equalTo($job))
            ->willReturn($dependentJobContext);
        $dependentJob->expects($this->once())
            ->method('saveDependentJob')
            ->with($this->equalTo($dependentJobContext));

        $processor = new PreExportMessageProcessor(
            $jobRunner,
            self::getMessageProducer(),
            $this->createTokenStorageMock(),
            $dependentJob,
            $this->createLoggerMock(),
            100
        );

        $organization = $this->createOrganizationMock();
        $organizationRepository = $this->createOrganizationRepositoryMock();
        $organizationRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo(22))
            ->willReturn($organization);

        $doctrineHelper = $this->createDoctrineHelperMock();
        $doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->equalTo(Organization::class))
            ->willReturn($organizationRepository);
        $processor->setDoctrineHelper($doctrineHelper);

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler->expects($this->once())
            ->method('getExportingEntityIds')
            ->willReturn([]);
        $processor->setExportHandler($exportHandler);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessor::ACK, $result);
        self::assertMessageSent(
            Topics::EXPORT,
            new Message(
                [
                    'jobName'        => 'test',
                    'processorAlias' => 'test',
                    'outputFormat'   => 'csv',
                    'organizationId' => '22',
                    'exportType'     => 'export',
                    'options'        => [],
                    'jobId'          => 10
                ],
                MessagePriority::LOW
            )
        );
    }

    /**
     * @param int $id
     * @param Job $rootJob
     *
     * @return Job
     */
    private function createJob($id, $rootJob = null)
    {
        $job = new Job();
        $job->setId($id);
        if ($rootJob instanceof Job) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobService
     */
    private function createDependentJobMock()
    {
        return $this->createMock(DependentJobService::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobContext
     */
    private function createDependentJobContextMock()
    {
        return $this->createMock(DependentJobContext::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->createMock(ExportHandler::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private function createDoctrineHelperMock()
    {
        return $this->createMock(DoctrineHelper::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        $token = $this->createTokenMock();
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($this->createUserStub());

        $tokeStorage = $this->createMock(TokenStorageInterface::class);
        $tokeStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        return $tokeStorage;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenInterface
     */
    private function createTokenMock()
    {
        return $this->createMock(TokenInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Organization
     */
    private function createOrganizationMock()
    {
        return $this->createMock(Organization::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|OrganizationRepository
     */
    private function createOrganizationRepositoryMock()
    {
        return $this->createMock(OrganizationRepository::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserInterface
     */
    private function createUserStub()
    {
        $user = $this->createPartialMock(
            UserInterface::class,
            ['getId', 'getEmail', 'getRoles', 'getPassword', 'getSalt', 'getUsername', 'eraseCredentials']
        );
        $user->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getEmail');

        return $user;
    }
}
