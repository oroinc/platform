<?php
namespace Oro\Bundle\DataGridBundle\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
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

    /**
     * @param ExportHandler $exportHandler
     */
    public function setExportHandler(ExportHandler $exportHandler)
    {
        $this->exportHandler = $exportHandler;
    }

    /**
     * @param DatagridExportConnector $exportConnector
     */
    public function setExportConnector(DatagridExportConnector $exportConnector)
    {
        $this->exportConnector = $exportConnector;
    }

    /**
     * @param ExportProcessor $exportProcessor
     */
    public function setExportProcessor(ExportProcessor $exportProcessor)
    {
        $this->exportProcessor = $exportProcessor;
    }

    /**
     * @param WriterChain $writerChain
     */
    public function setWriterChain(WriterChain $writerChain)
    {
        $this->writerChain = $writerChain;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
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

        $exportResult = $this->exportHandler->handle(
            $this->exportConnector,
            $this->exportProcessor,
            $this->writer,
            $body['parameters'],
            $body['batchSize'],
            $body['format']
        );

        return $exportResult;
    }
    
    /**
     * {@inheritDoc}
     */
    protected function getMessageBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'jobId' => null,
            'format' => null,
            'batchSize' => 200,
            'parameters' => [
                'gridName' => null,
                'gridParameters' => [],
                FormatterProvider::FORMAT_TYPE => 'excel',
            ],
        ], $body);
        $body['parameters']['pageSize'] = $body['batchSize'];

        if (! isset($body['jobId'], $body['parameters']['gridName'], $body['format'])) {
            $this->logger->critical('Got invalid message');

            return false;
        }

        $this->writer = $this->writerChain->getWriter($body['format']);
        if (! $this->writer instanceof FileStreamWriter) {
            $this->logger->critical(sprintf('Invalid format: "%s"', $body['format']));

            return false;
        }

        return $body;
    }
}
