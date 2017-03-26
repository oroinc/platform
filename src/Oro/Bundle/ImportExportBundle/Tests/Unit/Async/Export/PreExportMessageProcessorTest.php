<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class PreExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testPreExportProcessCanBeConstructedWithRequiredAttributes()
    {
        new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineHelperMock(),
            $this->createTokenStorageInterfaceMock(),
            $this->createLoggerInterfaceMock(),
            $this->createDependentJobMock(),
            100
        );
    }

    public function testPreExportProcessShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::PRE_EXPORT], PreExportMessageProcessor::getSubscribedTopics());
    }

    public function testShouldLogErrorAndRejectMessageIfMessageWasInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[PreExportMessageProcessor] Got invalid message: "[]"');

        $processor = new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineHelperMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createDependentJobMock(),
            100
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn('[]')
        ;

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogErrorAndRejectMessageIfTokenInvalid()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[PreExportMessageProcessor] Cannot set security token');

        $processor = new PreExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createMessageProducerInterfaceMock(),
            $this->createDoctrineHelperMock(),
            $this->createTokenStorageInterfaceMock(),
            $logger,
            $this->createDependentJobMock(),
            100
        );
        $processor->setTokenSerializer($this->createTokenSerializerMock());

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'jobName' => 'test',
                'processorAlias' => 'test',
                'securityToken' => 'test',
            ]));

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldCreateAndSendToProducerSubJobsAndCreateDependedJobAndACKMessage()
    {
        $job = $this->getJob(1);
        $childJob1 = $this->getJob(2, $job);
        $childJob2 = $this->getJob(3, $job);
        $childJob = $this->getJob(10, $job);
        $user = new User();
        $user->setId(1);
        $user->setEmail('useremail@example.com');

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(1, 'oro_importexport.pre_export.test.user_1')
            ->will(
                $this->returnCallback(function ($jobId, $name, $callback) use ($jobRunner, $childJob) {
                    return $callback($jobRunner, $childJob);
                })
            );

        $jobRunner
            ->expects($this->at(0))
            ->method('createDelayed')
            ->with('oro_importexport.pre_export.test.user_1.chunk.1')
            ->will(
                $this->returnCallback(function ($jobId, $callback) use ($jobRunner, $childJob1) {
                    return $callback($jobRunner, $childJob1);
                })
            );

        $jobRunner
            ->expects($this->at(1))
            ->method('createDelayed')
            ->with('oro_importexport.pre_export.test.user_1.chunk.2')
            ->will(
                $this->returnCallback(function ($jobId, $callback) use ($jobRunner, $childJob2) {
                    return $callback($jobRunner, $childJob2);
                })
            );

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler
            ->expects($this->once())
            ->method('getExportingEntityIds')
            ->with('test', 'export', 'test', [])
            ->willReturn([1,2,3,4]);

        $dependentContext = $this->createDependentJobContextMock();
        $dependentContext
            ->expects($this->once())
            ->method('addDependentJob')
            ->with(Topics::POST_EXPORT);

        $dependentJob = $this->createDependentJobMock();
        $dependentJob
            ->expects($this->once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentContext);

        $dependentJob
            ->expects($this->once())
            ->method('saveDependentJob')
            ->with($dependentContext);

        $messageProducer = $this->createMessageProducerInterfaceMock();
        $messageProducer
            ->expects($this->at(0))
            ->method('send')
            ->with(
                Topics::EXPORT,
                [
                    'jobName' => 'test',
                    'processorAlias' => 'test',
                    'securityToken' => 'test',
                    'outputFormat' => 'csv',
                    'organizationId' => null,
                    'exportType' => 'export',
                    'options' => ['ids' => [1,2]],
                    'jobId' => 2,
                 ]
            );

        $messageProducer
            ->expects($this->at(1))
            ->method('send')
            ->with(
                Topics::EXPORT,
                [
                    'jobName' => 'test',
                    'processorAlias' => 'test',
                    'securityToken' => 'test',
                    'outputFormat' => 'csv',
                    'organizationId' => null,
                    'exportType' => 'export',
                    'options' => ['ids' => [3,4]],
                    'jobId' => 3,
                ]
            );

        $tokenInterface = $this->createTokenInterfaceMock();
        $tokenInterface
            ->expects($this->exactly(3))
            ->method('getUser')
            ->willReturn($user);
        $tokenStorage = $this->createTokenStorageInterfaceMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($tokenInterface);
        $tokenStorage
            ->expects($this->exactly(3))
            ->method('getToken')
            ->willReturn($tokenInterface)
        ;

        $processor = new PreExportMessageProcessor(
            $exportHandler,
            $jobRunner,
            $messageProducer,
            $this->createDoctrineHelperMock(),
            $tokenStorage,
            $this->createLoggerInterfaceMock(),
            $dependentJob,
            2
        );

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode([
                'jobName' => 'test',
                'processorAlias' => 'test',
                'securityToken' => 'test',
            ]));
        $message
            ->expects($this->once())
            ->method('getMessageId')
            ->willReturn(1);

        $tokenSerializer = $this->createTokenSerializerMock();
        $tokenSerializer
            ->expects($this->once())
            ->method('deserialize')
            ->with('test')
            ->willReturn($tokenInterface);
        $processor->setTokenSerializer($tokenSerializer);

        $result = $processor->process($message, $this->createSessionMock());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    protected function getJob($id, $rootJob = null)
    {
        $job = new Job();
        $job->setId($id);
        if ($rootJob instanceof Job) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageInterface
     */
    private function createMessageMock()
    {
        return $this->createMock(MessageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DependentJobService
     */
    protected function createDependentJobMock()
    {
        return $this->createMock(DependentJobService::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DependentJobContext
     */
    private function createDependentJobContextMock()
    {
        return $this->createMock(DependentJobContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createMessageProducerInterfaceMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->createMock(ExportHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperMock()
    {
        return $this->createMock(DoctrineHelper::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerInterfaceMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    private function createTokenStorageInterfaceMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenSerializerInterface
     */
    private function createTokenSerializerMock()
    {
        return $this->createMock(TokenSerializerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    private function createTokenInterfaceMock()
    {
        return $this->createMock(TokenInterface::class);
    }
}
