<?php

namespace Oro\Bundle\DataGridBundle\Async\Export\Executor;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\MaterializedView\MaterializedViewByDatagridFactory;
use Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteMaterializedViewTopic;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Prepares datagrid data export:
 * - creates {@see MaterializedView}
 * - creates child jobs and sends them to MQ within messages {@see DatagridExportTopic}
 * - creates dependent messages: {@see DeleteMaterializedViewTopic} and {@see PostExportTopic}
 */
class DatagridPreExportOrmExecutor implements DatagridPreExportExecutorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private MessageProducerInterface $messageProducer;

    private MaterializedViewManager $materializedViewManager;

    private MaterializedViewByDatagridFactory $materializedViewByDatagridFactory;

    private DependentJobService $dependentJobService;

    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        MessageProducerInterface $messageProducer,
        MaterializedViewManager $materializedViewManager,
        MaterializedViewByDatagridFactory $materializedViewByDatagridFactory,
        DependentJobService $dependentJobService,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->messageProducer = $messageProducer;
        $this->materializedViewManager = $materializedViewManager;
        $this->materializedViewByDatagridFactory = $materializedViewByDatagridFactory;
        $this->dependentJobService = $dependentJobService;
        $this->tokenAccessor = $tokenAccessor;

        $this->logger = new NullLogger();
    }

    public function isSupported(DatagridInterface $datagrid, array $options): bool
    {
        return $datagrid->getDatasource() instanceof OrmDatasource && $this->tokenAccessor->getUserId();
    }

    /**
     * @param JobRunner $jobRunner
     * @param Job $job
     * @param DatagridInterface $datagrid
     * @param array $options Message body of the {@see DatagridPreExportTopic} topic.
     *
     * @return bool
     */
    public function run(JobRunner $jobRunner, Job $job, DatagridInterface $datagrid, array $options): bool
    {
        if ($this->tokenAccessor->getUserId() === null) {
            $this->logger->error(
                'Cannot execute job {jobName}: no authenticated user is found',
                ['jobName' => $job->getName(), 'datagrid' => $datagrid, 'options' => $options]
            );

            return false;
        }

        $materializedView = $this->materializedViewByDatagridFactory->createByDatagrid($datagrid);
        /** @var OrmDatasource $ormDatasource */
        $ormDatasource = $datagrid->getDatasource();

        $this->addPostExportMessages(
            $job->getRootJob(),
            $materializedView->getName(),
            $ormDatasource->getRootEntityName(),
            $options['outputFormat'],
            $options['notificationTemplate']
        );

        $childMessages = $this->getChildMessages(
            $options['contextParameters'],
            $materializedView->getName(),
            $options['batchSize'],
            $options['outputFormat']
        );

        foreach ($childMessages as $index => $childMessageBody) {
            $jobRunner->createDelayed(
                $this->getJobName($options['contextParameters']['gridName'], $options['outputFormat'], $index),
                $this->getJobStartCallback($childMessageBody)
            );
        }

        $this->logger->info(
            'Created {count} batches from the materialized view {materializedViewName}',
            [
                'count' => ($index ?? 0) + 1,
                'materializedViewName' => $materializedView->getName(),
                'jobName' => $job->getName(),
            ]
        );

        return true;
    }

    private function getJobName(string $gridName, string $outputFormat, int $index): string
    {
        return sprintf(
            '%s.%s.user_%s.%s.chunk.%s',
            DatagridExportTopic::getName(),
            $gridName,
            $this->tokenAccessor->getUserId(),
            $outputFormat,
            $index + 1
        );
    }

    private function getJobStartCallback(array $childMessageBody): callable
    {
        return function (JobRunner $jobRunner, Job $childJob) use ($childMessageBody) {
            $this->messageProducer->send(
                DatagridExportTopic::getName(),
                new Message($childMessageBody + ['jobId' => $childJob->getId()], MessagePriority::LOW)
            );

            return true;
        };
    }

    private function getChildMessages(
        array $contextParameters,
        string $materializedViewName,
        int $rowsLimit,
        string $outputFormat
    ): \Generator {
        $rowsOffset = 0;
        $rowsCount = $this->materializedViewManager
            ->getRepository($materializedViewName)
            ->getRowsCount();

        $this->logger->info(
            'Creating batches from {rowsCount} rows of the materialized view {materializedViewName}',
            ['rowsCount' => $rowsCount, 'rowsLimit' => $rowsLimit, 'materializedViewName' => $materializedViewName]
        );

        do {
            yield [
                'contextParameters' => array_merge(
                    $contextParameters,
                    [
                        'materializedViewName' => $materializedViewName,
                        'rowsOffset' => $rowsOffset,
                        'rowsLimit' => $rowsLimit,
                    ]
                ),
                'outputFormat' => $outputFormat,
                'writerBatchSize' => $rowsLimit,
            ];
        } while (($rowsOffset += $rowsLimit) < $rowsCount);
    }

    private function addPostExportMessages(
        Job $rootJob,
        string $materializedViewName,
        string $exportedEntityClass,
        string $outputFormat,
        string $notificationTemplate
    ): void {
        $this->dependentJobService
            ->addDependentMessages(
                $rootJob,
                [
                    DeleteMaterializedViewTopic::getName() => ['materializedViewName' => $materializedViewName],
                    PostExportTopic::getName() => [
                        'jobId' => $rootJob->getId(),
                        'jobName' => $rootJob->getName(),
                        'recipientUserId' => $this->tokenAccessor->getUserId(),
                        'exportType' => ProcessorRegistry::TYPE_EXPORT,
                        'outputFormat' => $outputFormat,
                        'entity' => $exportedEntityClass,
                        'notificationTemplate' => $notificationTemplate,
                    ],
                ]
            );
    }
}
