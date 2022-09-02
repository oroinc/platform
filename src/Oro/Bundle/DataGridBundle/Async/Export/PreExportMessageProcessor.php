<?php

namespace Oro\Bundle\DataGridBundle\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Responsible for formatting body for export
 */
class PreExportMessageProcessor extends PreExportMessageProcessorAbstract
{
    /**
     * @var ExportHandler
     */
    protected $exportHandler;

    /**
     * @var DatagridExportIdFetcher
     */
    protected $exportIdFetcher;

    public function setExportHandler(ExportHandler $exportHandler)
    {
        $this->exportHandler = $exportHandler;
    }

    public function setExportIdFetcher(DatagridExportIdFetcher $exportIdFetcher)
    {
        $this->exportIdFetcher = $exportIdFetcher;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [DatagridPreExportTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    protected function getJobUniqueName(array $body)
    {
        return sprintf(
            'oro_datagrid.pre_export.%s.user_%s.%s',
            $body['parameters']['gridName'],
            $this->getUser()->getId(),
            $body['format']
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getExportingEntityIds(array $body)
    {
        $contextParameters = new ParameterBag($body['parameters']['gridParameters']);
        $contextParameters->set(
            ParameterBag::DATAGRID_MODES_PARAMETER,
            [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]
        );
        $body['parameters']['gridParameters'] = $contextParameters;

        return $this->exportHandler->getExportingEntityIds(
            $this->exportIdFetcher,
            $body['parameters']
        );
    }

    /**
     * {@inheritdoc}
     *
     * Adds an extra ability to create delayed jobs divided in batches by page number instead of entity IDs.
     */
    protected function getRunUniqueJobCallback($jobUniqueName, array $body)
    {
        return function (JobRunner $jobRunner, Job $job) use ($jobUniqueName, $body) {
            $this->addDependentJob($job->getRootJob(), $body);

            if (!empty($body['parameters']['exportByPages'])) {
                $totalPages = (int) ceil($this->exportIdFetcher->getTotalRecords() / $this->getPageOrBatchSize($body));
                $batches = array_map(static fn (int $page) => ['exactPage' => $page], range(1, $totalPages));
            } else {
                $exportingEntityIds = $this->getExportingEntityIds($body);
                $batches = $this->splitByPageSizeOrBatchSize($exportingEntityIds, $body);
            }

            if (empty($batches)) {
                $jobRunner->createDelayed(
                    sprintf('%s.chunk.%s', $jobUniqueName, 1),
                    $this->getDelayedJobCallback($body)
                );
            }

            foreach ($batches as $key => $batchData) {
                $jobRunner->createDelayed(
                    sprintf('%s.chunk.%s', $jobUniqueName, ++$key),
                    $this->getDelayedJobCallback($body, $batchData)
                );
            }

            return true;
        };
    }

    protected function splitByPageSizeOrBatchSize(array $ids, array $body): array
    {
        return array_chunk($ids, $this->getPageOrBatchSize($body));
    }

    protected function getPageOrBatchSize(array $messageBody): int
    {
        return $messageBody['parameters']['pageSize'] ?? $this->getBatchSize();
    }

    /**
     * {@inheritDoc}
     */
    protected function getDelayedJobCallback(array $body, array $batchData = [])
    {
        if (!empty($batchData)) {
            if (!empty($body['parameters']['exportByPages'])) {
                $body['parameters']['exactPage'] = $batchData['exactPage'];
            } else {
                $body['parameters']['gridParameters']['_export']['ids'] = $batchData;
            }
        }

        return function (JobRunner $jobRunner, Job $child) use ($body) {
            $this->producer->send(
                DatagridExportTopic::getName(),
                new Message(
                    array_merge($body, ['jobId' => $child->getId()]),
                    MessagePriority::LOW
                )
            );
        };
    }

    /**
     * {@inheritDoc}
     */
    protected function getMessageBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'batchSize' => $this->getPageOrBatchSize($body),
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
        ], $body);

        $body['entity'] = $this->exportHandler->getEntityName(
            $this->exportIdFetcher,
            $body['parameters']
        );

        // prepare body for dependent job message
        $body['jobName'] = $body['parameters']['gridName'];
        $body['outputFormat'] = $body['format'];

        return $body;
    }
}
