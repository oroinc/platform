<?php

namespace Oro\Bundle\DataGridBundle\Async\Export;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Exports a batch of rows during the datagrid data export.
 */
class DatagridExportMessageProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    private JobRunner $jobRunner;

    private ExportHandler $exportHandler;

    private ExportProcessor $exportProcessor;

    private ItemReaderInterface $exportItemReader;

    private WriterChain $writerChain;

    private FileManager $fileManager;

    public function __construct(
        JobRunner $jobRunner,
        ExportHandler $exportHandler,
        ExportProcessor $exportProcessor,
        ItemReaderInterface $exportItemReader,
        WriterChain $writerChain,
        FileManager $fileManager
    ) {
        $this->jobRunner = $jobRunner;
        $this->exportHandler = $exportHandler;
        $this->exportProcessor = $exportProcessor;
        $this->exportItemReader = $exportItemReader;
        $this->writerChain = $writerChain;
        $this->fileManager = $fileManager;

        $this->logger = new NullLogger();
    }

    public static function getSubscribedTopics(): array
    {
        return [DatagridExportTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();
        $exportWriter = $this->getExportWriter($messageBody['outputFormat']);
        if ($exportWriter === null) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runDelayed(
            $messageBody['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($messageBody, $exportWriter) {
                $exportResult = $this->exportHandler->handle(
                    $this->exportItemReader,
                    $this->exportProcessor,
                    $exportWriter,
                    $messageBody['contextParameters'],
                    $messageBody['writerBatchSize'],
                    $messageBody['outputFormat']
                );

                $this->logger->info(
                    'Export of the batch with offset {rowsStart}, limit {rowsLimit} is finished. Success: {success}. '
                    . 'Read: {readsCount}. Errors: {errorsCount}',
                    [
                        'rowsOffset' => $messageBody['contextParameters']['rowsOffset'],
                        'rowsLimit' => $messageBody['contextParameters']['rowsLimit'],
                        'success' => $exportResult['success'] ? 'Yes' : 'No',
                        'readsCount' => $exportResult['readsCount'],
                        'errorsCount' => $exportResult['errorsCount'],
                    ]
                );

                $this->saveJobResult($job, $exportResult);

                return (bool)$exportResult['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    private function saveJobResult(Job $job, array $data): void
    {
        if (!empty($data['errors'])) {
            $data['errorLogFile'] = str_replace('.', '', uniqid('export', true)) . '.json';
            $this->fileManager->writeToStorage(
                json_encode($data['errors'], JSON_THROW_ON_ERROR),
                $data['errorLogFile']
            );
        }

        $job->setData($data);
    }

    private function getExportWriter(string $outputFormat): ?FileStreamWriter
    {
        $writer = $this->writerChain->getWriter($outputFormat);
        if (!$writer instanceof FileStreamWriter) {
            $this->logger->error(
                'Export writer for output format {outputFormat} was expected to be {expectedClass}, got {actualClass}',
                [
                    'outputFormat' => $outputFormat,
                    'expectedClass' => FileStreamWriter::class,
                    'actualClass' => get_debug_type($writer),
                ]
            );

            return null;
        }

        return $writer;
    }
}
