<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\PreExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\ImportExportBundle\Async\Topics as ImportExportTopics;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler as DefaultExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\Message as ClientMessage;
use Oro\Component\MessageQueue\Client\MessagePriority;
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

    private const BATCH_SIZE = 100;
    private const ENTITY_NAME = 'Acme';

    private DatagridExportIdFetcher|\PHPUnit\Framework\MockObject\MockObject $exportIdFetcher;

    private DependentJobService|\PHPUnit\Framework\MockObject\MockObject $dependentJobService;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private ExportHandler|\PHPUnit\Framework\MockObject\MockObject $exportHandler;

    private PreExportMessageProcessor $processor;

    protected function setUp(): void
    {
        $this->dependentJobService = $this->createMock(DependentJobService::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        $this->processor = new PreExportMessageProcessor(
            $this->jobRunner,
            self::getMessageProducer(),
            $tokenStorage,
            $this->dependentJobService,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DefaultExportHandler::class),
            self::BATCH_SIZE
        );

        $this->exportHandler = $this->createMock(ExportHandler::class);
        $this->exportHandler->expects(self::any())
            ->method('getEntityName')
            ->willReturn(self::ENTITY_NAME);

        $this->processor->setExportHandler($this->exportHandler);

        $this->exportIdFetcher = $this->createMock(DatagridExportIdFetcher::class);
        $this->processor->setExportIdFetcher($this->exportIdFetcher);
    }

    public function testShouldReturnSubscribedTopics(): void
    {
        self::assertEquals([DatagridPreExportTopic::getName()], PreExportMessageProcessor::getSubscribedTopics());
    }

    public function testShouldReturnMessageACKOnExportSuccess(): void
    {
        $gridName = 'grid_name';
        $format = 'csv';

        $jobUniqueName = 'oro_datagrid.pre_export.grid_name.user_1.csv';
        $message = new Message();
        $message->setMessageId('123');
        $message->setBody(
            JSON::encode([
                'format' => $format,
                'parameters' => [
                    'gridName' => $gridName,
                    'gridParameters' => [],
                    FormatterProvider::FORMAT_TYPE => 'excel',
                ],
                'exportType' => ProcessorRegistry::TYPE_EXPORT,
                'jobName' => $gridName,
                'outputFormat' => $format,
                'entity' => self::ENTITY_NAME,
            ])
        );
        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->with('123', $jobUniqueName)
            ->willReturnCallback(fn ($jobId, $name, $callback) => $callback($this->jobRunner, $childJob));
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with($jobUniqueName . '.chunk.1')
            ->willReturnCallback(fn ($name, $callback) => $callback($this->jobRunner, $childJob));

        $dependentJobContext = $this->createMock(DependentJobContext::class);
        $dependentJobContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                ImportExportTopics::POST_EXPORT,
                [
                    'jobId' => 1,
                    'recipientUserId' => 1,
                    'jobName' => $gridName,
                    'exportType' => 'export',
                    'outputFormat' => $format,
                    'entity' => self::ENTITY_NAME,
                    'notificationTemplate' => 'export_result',
                ]
            );

        $this->dependentJobService->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJobService->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->exportHandler->expects(self::once())
            ->method('getExportingEntityIds')
            ->willReturn([]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            DatagridExportTopic::getName(),
            new ClientMessage(
                [
                    'format' => $format,
                    'batchSize' => self::BATCH_SIZE,
                    'parameters' => [
                        'gridName' => $gridName,
                        'gridParameters' => [],
                        'format_type' => 'excel',
                    ],
                    'exportType' => 'export',
                    'entity' => self::ENTITY_NAME,
                    'jobName' => $gridName,
                    'outputFormat' => $format,
                    'jobId' => 10,
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

    public function testShouldUsePageSizeAndReturnMessageACKOnExportSuccess(): void
    {
        $gridName = 'grid_name';
        $format = 'csv';
        $entityName = 'Acme';

        $jobUniqueName = 'oro_datagrid.pre_export.grid_name.user_1.csv';
        $message = new Message();
        $message->setMessageId('123');
        $pageSize = 4242;
        $message->setBody(
            JSON::encode([
                'format' => $format,
                'parameters' => [
                    'gridName' => $gridName,
                    'gridParameters' => [],
                    'pageSize' => $pageSize,
                    FormatterProvider::FORMAT_TYPE => 'excel',
                ],
                'exportType' => ProcessorRegistry::TYPE_EXPORT,
                'jobName' => $gridName,
                'outputFormat' => $format,
                'entity' => $entityName,
            ])
        );
        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->with('123', $jobUniqueName)
            ->willReturnCallback(fn ($jobId, $name, $callback) => $callback($this->jobRunner, $childJob));
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with($jobUniqueName . '.chunk.1')
            ->willReturnCallback(fn ($name, $callback) => $callback($this->jobRunner, $childJob));

        $dependentJobContext = $this->createMock(DependentJobContext::class);
        $dependentJobContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                ImportExportTopics::POST_EXPORT,
                [
                    'jobId' => 1,
                    'recipientUserId' => 1,
                    'jobName' => $gridName,
                    'exportType' => 'export',
                    'outputFormat' => $format,
                    'entity' => $entityName,
                    'notificationTemplate' => 'export_result',
                ]
            );

        $this->dependentJobService->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJobService->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->exportHandler->expects(self::once())
            ->method('getExportingEntityIds')
            ->willReturn([]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        self::assertMessageSent(
            DatagridExportTopic::getName(),
            new ClientMessage(
                [
                    'format' => $format,
                    'batchSize' => $pageSize,
                    'parameters' => [
                        'gridName' => $gridName,
                        'gridParameters' => [],
                        'pageSize' => $pageSize,
                        'format_type' => 'excel',
                    ],
                    'exportType' => 'export',
                    'entity' => $entityName,
                    'jobName' => $gridName,
                    'outputFormat' => $format,
                    'jobId' => 10,
                ],
                MessagePriority::LOW
            )
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldSplitByPagesAndReturnMessageACKOnExportSuccess(): void
    {
        $gridName = 'grid_name';
        $format = 'csv';
        $entityName = 'Acme';

        $jobUniqueName = 'oro_datagrid.pre_export.grid_name.user_1.csv';
        $message = new Message();
        $message->setMessageId('123');
        $pageSize = 4242;
        $message->setBody(
            JSON::encode([
                'format' => $format,
                'parameters' => [
                    'gridName' => $gridName,
                    'gridParameters' => [],
                    'pageSize' => $pageSize,
                    'exportByPages' => true,
                    FormatterProvider::FORMAT_TYPE => 'excel',
                ],
                'exportType' => ProcessorRegistry::TYPE_EXPORT,
                'jobName' => $gridName,
                'outputFormat' => $format,
                'entity' => $entityName,
            ])
        );
        $job = $this->createJob(1);
        $childJob = $this->createJob(10, $job);

        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->with('123', $jobUniqueName)
            ->willReturnCallback(fn ($jobId, $name, $callback) => $callback($this->jobRunner, $childJob));
        $this->jobRunner->expects(self::exactly(2))
            ->method('createDelayed')
            ->withConsecutive([$jobUniqueName . '.chunk.1'], [$jobUniqueName . '.chunk.2'])
            ->willReturnCallback(fn ($name, $callback) => $callback($this->jobRunner, $childJob));

        $dependentJobContext = $this->createMock(DependentJobContext::class);
        $dependentJobContext->expects(self::once())
            ->method('addDependentJob')
            ->with(
                ImportExportTopics::POST_EXPORT,
                [
                    'jobId' => 1,
                    'recipientUserId' => 1,
                    'jobName' => $gridName,
                    'exportType' => 'export',
                    'outputFormat' => $format,
                    'entity' => $entityName,
                    'notificationTemplate' => 'export_result',
                ]
            );

        $this->dependentJobService->expects(self::once())
            ->method('createDependentJobContext')
            ->with($job)
            ->willReturn($dependentJobContext);
        $this->dependentJobService->expects(self::once())
            ->method('saveDependentJob')
            ->with($dependentJobContext);

        $this->exportHandler->expects(self::never())
            ->method('getExportingEntityIds')
            ->willReturn([]);

        $this->exportIdFetcher->expects(self::once())
            ->method('getTotalRecords')
            ->willReturn(6363);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
        $expectedMessageBody1 = [
            'format' => $format,
            'batchSize' => $pageSize,
            'parameters' => [
                'gridName' => $gridName,
                'gridParameters' => [],
                'pageSize' => $pageSize,
                'exportByPages' => true,
                'format_type' => 'excel',
                'exactPage' => 1,
            ],
            'exportType' => 'export',
            'entity' => $entityName,
            'jobName' => $gridName,
            'outputFormat' => $format,
            'jobId' => 10,
        ];
        self::assertMessageSent(
            DatagridExportTopic::getName(),
            new ClientMessage($expectedMessageBody1, MessagePriority::LOW)
        );

        $expectedMessageBody2 = $expectedMessageBody1;
        $expectedMessageBody2['parameters']['exactPage'] = 2;
        self::assertMessageSent(
            DatagridExportTopic::getName(),
            new ClientMessage($expectedMessageBody2, MessagePriority::LOW)
        );
    }
}
