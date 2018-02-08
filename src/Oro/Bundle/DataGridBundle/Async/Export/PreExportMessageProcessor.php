<?php
namespace Oro\Bundle\DataGridBundle\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportIdFetcher;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\ImportExportBundle\Async\Export\PreExportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

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

    /**
     * @param ExportHandler $exportHandler
     */
    public function setExportHandler(ExportHandler $exportHandler)
    {
        $this->exportHandler = $exportHandler;
    }

    /**
     * @param DatagridExportIdFetcher $exportIdFetcher
     */
    public function setExportIdFetcher(DatagridExportIdFetcher $exportIdFetcher)
    {
        $this->exportIdFetcher = $exportIdFetcher;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRE_EXPORT];
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

        $ids = $this->exportHandler->getExportingEntityIds(
            $this->exportIdFetcher,
            $body['parameters']
        );

        return $ids;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDelayedJobCallback(array $body, array $ids = [])
    {
        if (! empty($ids)) {
            $body['parameters']['gridParameters']['_export']['ids'] = $ids;
        }

        return function (JobRunner $jobRunner, Job $child) use ($body) {
            $this->producer->send(
                Topics::EXPORT,
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
            'format' => null,
            'batchSize' => $this->batchSize,
            'parameters' => [
                'gridName' => null,
                'gridParameters' => [],
                FormatterProvider::FORMAT_TYPE => 'excel',
            ],
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
        ], $body);

        if (! isset($body['parameters']['gridName'], $body['format'])) {
            $this->logger->critical('Got invalid message');

            return false;
        }

        // prepare body for dependent job message
        $body['jobName'] = $body['parameters']['gridName'];
        $body['outputFormat'] = $body['format'];

        return $body;
    }
}
