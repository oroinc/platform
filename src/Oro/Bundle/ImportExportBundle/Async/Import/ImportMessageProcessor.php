<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\PostponedRowsHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Process async import message
 */
class ImportMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var ImportHandler
     */
    protected $importHandler;

    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ImportExportResultSummarizer
     */
    protected $importExportResultSummarizer;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var PostponedRowsHandler
     */
    protected $postponedRowsHandler;

    public function __construct(
        JobRunner $jobRunner,
        ImportExportResultSummarizer $importExportResultSummarizer,
        LoggerInterface $logger,
        FileManager $fileManager,
        ImportHandler $importHandler,
        PostponedRowsHandler $postponedRowsHandler
    ) {
        $this->importHandler = $importHandler;
        $this->jobRunner = $jobRunner;
        $this->importExportResultSummarizer = $importExportResultSummarizer;
        $this->logger = $logger;
        $this->fileManager = $fileManager;
        $this->postponedRowsHandler = $postponedRowsHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (!$body = $this->getNormalizeBody($message)) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        try {
            $result = $this->jobRunner->runDelayed(
                $body['jobId'],
                function (JobRunner $jobRunner, Job $job) use ($body) {
                    return $this->handleImport($body, $job, $jobRunner);
                }
            );
        } catch (JobRedeliveryException $exception) {
            return self::REQUEUE;
        }

        $this->fileManager->deleteFile($body['fileName']);

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param MessageInterface $message
     * @return array|null
     */
    protected function getNormalizeBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());

        if (!isset(
            $body['fileName'],
            $body['jobName'],
            $body['processorAlias'],
            $body['jobId'],
            $body['process'],
            $body['originFileName'],
            $body['userId']
        )) {
            return null;
        }

        return array_replace_recursive([
            'options' => []
        ], $body);
    }

    /**
     * @param array $body
     * @param Job $job
     * @param JobRunner $jobRunner
     * @return bool
     */
    protected function handleImport(array $body, Job $job, JobRunner $jobRunner)
    {
        $importFileName = $body['fileName'];
        try {
            $filePath = $this->fileManager->writeToTmpLocalStorage($importFileName);
            $this->importHandler->setImportingFileName($filePath);
            $result = $this->importHandler->handle(
                $body['process'],
                $body['jobName'],
                $body['processorAlias'],
                $body['options']
            );

            if (!empty($result['deadlockDetected'])) {
                throw new JobRedeliveryException();
            }
            if (!empty($result['postponedRows'])) {
                $fileName = $this
                    ->postponedRowsHandler->writeRowsToFile($result['postponedRows'], $importFileName);

                $this->postponedRowsHandler->postpone($jobRunner, $job, $fileName, $body, $result);
            }

            $this->saveJobResult($job, $result);
            $this->logger->info(
                $this->importExportResultSummarizer->getImportSummaryMessage(
                    array_merge(['originFileName' => $body['originFileName']], $result),
                    $body['process'],
                    $this->logger
                )
            );

            return (bool)$result['success'];
        } finally {
            @unlink($filePath);
        }
    }

    protected function saveJobResult(Job $job, array $data)
    {
        if (isset($data['errors']) && !empty(($data['errors']))) {
            $data['errorLogFile'] = $this->saveToStorageErrorLog($data['errors']);
        }

        unset($data['message'], $data['importInfo'], $data['errors'], $data['postponedRows']);

        $job->setData($data);
    }

    /**
     * @param array $errors
     * @return string
     */
    protected function saveToStorageErrorLog(array &$errors)
    {
        /** @var FileManager $fileManager */
        $fileManager = $this->fileManager;
        $errorAsJson = json_encode($errors);

        $fileName = str_replace('.', '', uniqid('import')) . '.json';

        $fileManager->writeToStorage($errorAsJson, $fileName);

        return $fileName;
    }
}
