<?php

namespace Oro\Bundle\DataGridBundle\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Process data grid export async message.
 */
class ExportMessageProcessor extends ExportMessageProcessorAbstract
{
    /**
     * @var ExportHandler
     */
    protected $exportHandler;

    /**
     * @var DatagridExportConnector
     */
    protected $exportConnector;

    /**
     * @var ExportProcessor
     */
    protected $exportProcessor;

    /**
     * @var WriterChain
     */
    protected $writerChain;

    /**
     * @var FileStreamWriter
     */
    protected $writer;

    public function setExportHandler(ExportHandler $exportHandler)
    {
        $this->exportHandler = $exportHandler;
    }

    public function setExportConnector(DatagridExportConnector $exportConnector)
    {
        $this->exportConnector = $exportConnector;
    }

    public function setExportProcessor(ExportProcessor $exportProcessor)
    {
        $this->exportProcessor = $exportProcessor;
    }

    public function setWriterChain(WriterChain $writerChain)
    {
        $this->writerChain = $writerChain;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [DatagridExportTopic::getName()];
    }

    /**
     * {@inheritDoc}
     */
    protected function handleExport(array $body)
    {
        $contextParameters = new ParameterBag($body['parameters']['gridParameters']);
        $contextParameters->set(
            ParameterBag::DATAGRID_MODES_PARAMETER,
            [DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE]
        );
        $body['parameters']['gridParameters'] = $contextParameters;

        return $this->exportHandler->handle(
            $this->exportConnector,
            $this->exportProcessor,
            $this->writer,
            $body['parameters'],
            $body['batchSize'],
            $body['format']
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getMessageBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'batchSize' => 200,
        ], $body);
        $body['parameters']['pageSize'] = $body['batchSize'];

        $this->writer = $this->writerChain->getWriter($body['format']);
        if (!$this->writer instanceof FileStreamWriter) {
            $this->logger->critical(sprintf('Invalid format: "%s"', $body['format']));

            return false;
        }

        return $body;
    }
}
