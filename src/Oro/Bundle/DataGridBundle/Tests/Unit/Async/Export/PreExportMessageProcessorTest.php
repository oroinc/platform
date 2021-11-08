<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\ImportExportBundle\Async\Topics as ImportExportTopics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler as DefaultExportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\Message as ClientMessage;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
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
     */
    public function testShouldRejectMessageAndLogCriticalIfRequiredParametersAreMissing(
        string $loggerMessage,
        array $messageBody
    ) {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('critical')
            ->with($this->stringContains($loggerMessage));

        $processor = new PreExportMessageProcessor(
            $this->createMock(JobRunner::class),
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(DependentJobService::class),
            $logger,
            $this->createMock(DefaultExportHandler::class),
            100
        );

        $message = new Message();
        $message->setBody(JSON::encode($messageBody));
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldReturnMessageACKOnExportSuccess()
    {
        $jobUniqueName = 'oro_datagrid.pre_export.grid_name.user_1.csv';
        $message = new Message();
        $message->setMessageId('123');
        $message->setBody(JSON::encode([
            'format' => 'csv',
            'parameters' => ['gridName' => 'grid_name']
        ]));
        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects(self::once())
            ->method('runUnique')
            ->with('123', $jobUniqueName)
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
                    'email' => null,
                    'recipientUserId' => 1,
                    'jobName' => 'grid_name',
                    'exportType' => 'export',
                    'outputFormat' => 'csv',
                    'entity' => 'Acme',
                    'notificationTemplate' => 'export_result',
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

        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $user->expects(self::once())
            ->method('getEmail');

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        $processor = new PreExportMessageProcessor(
            $jobRunner,
            self::getMessageProducer(),
            $tokenStorage,
            $dependentJob,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DefaultExportHandler::class),
            100
        );

        $exportHandler = $this->createMock(ExportHandler::class);
        $exportHandler->expects(self::once())
            ->method('getExportingEntityIds')
            ->willReturn([]);
        $exportHandler->expects(self::once())
            ->method('getEntityName')
            ->willReturn('Acme');

        $processor->setExportHandler($exportHandler);
        $processor->setExportIdFetcher($this->createMock(DatagridExportIdFetcher::class));
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            Topics::EXPORT,
            new ClientMessage(
                [
                    'format'       => 'csv',
                    'batchSize'    => 100,
                    'parameters'   => [
                        'gridName'       => 'grid_name',
                        'gridParameters' => [],
                        'format_type'    => 'excel'
                    ],
                    'exportType'   => 'export',
                    'entity'       => 'Acme',
                    'jobName'      => 'grid_name',
                    'outputFormat' => 'csv',
                    'jobId'        => 10
                ],
                MessagePriority::LOW
            )
        );
    }

    private function createJob(int $id, Job $rootJob = null): Job
    {
        $job = new Job();
        $job->setId($id);
        if ($rootJob instanceof Job) {
            $job->setRootJob($rootJob);
        }

        return $job;
    }
}
