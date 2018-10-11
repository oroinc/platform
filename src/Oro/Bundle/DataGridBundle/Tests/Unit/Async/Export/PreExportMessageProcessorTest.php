<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
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
                'Got invalid message',
                ['format' => 'csv'],
            ],
            [
                'Got invalid message',
                ['parameters' => ['gridName' => 'name']],
            ],
        ];
    }

    /**
     * @dataProvider invalidMessageBodyProvider
     *
     * @param string $loggerMessage
     * @param array  $messageBody
     */
    public function testShouldRejectMessageAndLogCriticalIfRequiredParametersAreMissing($loggerMessage, $messageBody)
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($loggerMessage));

        $processor = new PreExportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createMessageProducerMock(),
            $this->createTokenStorageMock(),
            $this->createDependentJobServiceMock(),
            $logger,
            100
        );

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessor::REJECT, $result);
    }

    public function testShouldReturnMessageACKOnExportSuccess()
    {
        $jobUniqueName = 'oro_datagrid.pre_export.grid_name.user_1.csv';
        $message = new NullMessage();
        $message->setBody(json_encode([
            'format'     => 'csv',
            'parameters' => ['gridName' => 'grid_name'],
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
        $dependentJobContext->expects($this->once())
            ->method('addDependentJob');

        $dependentJob = $this->createDependentJobMock();
        $dependentJob->expects($this->once())
            ->method('createDependentJobContext')
            ->with($this->equalTo($job))
            ->willReturn($dependentJobContext);
        $dependentJob->expects($this->once())
            ->method('saveDependentJob')
            ->with($this->equalTo($dependentJobContext));

        $user = $this->createUserStub();
        $user->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $user->expects($this->once())
            ->method('getEmail');

        $token = $this->createTokenMock();
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage = $this->createTokenStorageMock();
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $processor = new PreExportMessageProcessor(
            $jobRunner,
            self::getMessageProducer(),
            $tokenStorage,
            $dependentJob,
            $this->createLoggerMock(),
            100
        );

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler->expects($this->once())
            ->method('getExportingEntityIds')
            ->willReturn([]);
        $processor->setExportHandler($exportHandler);

        $processor->setExportIdFetcher($this->createExportIdFetcherMock());

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(PreExportMessageProcessor::ACK, $result);
        self::assertMessageSent(
            Topics::EXPORT,
            new Message(
                [
                    'format'       => 'csv',
                    'batchSize'    => 100,
                    'parameters'   => [
                        'gridName'       => 'grid_name',
                        'gridParameters' => [],
                        'format_type'    => 'excel'
                    ],
                    'exportType'   => 'export',
                    'jobName'      => 'grid_name',
                    'outputFormat' => 'csv',
                    'jobId'        => 10
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
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
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
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DatagridExportIdFetcher
     */
    private function createExportIdFetcherMock()
    {
        return $this->createMock(DatagridExportIdFetcher::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobService
     */
    private function createDependentJobServiceMock()
    {
        return $this->createMock(DependentJobService::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenInterface
     */
    private function createTokenMock()
    {
        return $this->createMock(TokenInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobContext
     */
    private function createDependentJobContextMock()
    {
        return $this->createMock(DependentJobContext::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DependentJobService
     */
    private function createDependentJobMock()
    {
        return $this->createMock(DependentJobService::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|UserInterface
     */
    private function createUserStub()
    {
        return $this->createPartialMock(
            UserInterface::class,
            ['getId', 'getEmail', 'getRoles', 'getPassword', 'getSalt', 'getUsername', 'eraseCredentials']
        );
    }
}
