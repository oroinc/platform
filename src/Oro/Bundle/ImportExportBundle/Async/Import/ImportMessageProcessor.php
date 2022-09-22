<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Handler\PostponedRowsHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Process async import message
 */
class ImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ImportHandler $importHandler;

    private JobRunner $jobRunner;

    private LoggerInterface $logger;

    private ImportExportResultSummarizer $importExportResultSummarizer;

    private FileManager $fileManager;

    private PostponedRowsHandler $postponedRowsHandler;

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
        $messageBody = $message->getBody();

        try {
            $result = $this->jobRunner->runDelayed(
                $messageBody['jobId'],
                function (JobRunner $jobRunner, Job $job) use ($messageBody) {
                    return $this->handleImport($messageBody, $job, $jobRunner);
                }
            );
        } catch (JobRedeliveryException $exception) {
            return self::REQUEUE;
        }

        $this->fileManager->deleteFile($messageBody['fileName']);

        return $result ? self::ACK : self::REJECT;
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
        $fileManager = $this->fileManager;
        $errorAsJson = json_encode($errors);

        $fileName = str_replace('.', '', uniqid('import')) . '.json';

        $fileManager->writeToStorage($errorAsJson, $fileName);

        return $fileName;
    }

    public static function getSubscribedTopics()
    {
        return [ImportTopic::getName()];
    }
}
