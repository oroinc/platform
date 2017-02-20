<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CliImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var CliImportHandler
     */
    private $cliImportHandler;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ImportExportResultSummarizer
     */
    private $importExportResultSummarizer;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param CliImportHandler $cliImportHandler
     * @param JobRunner $jobRunner
     * @param ImportExportResultSummarizer $importExportResultSummarizer
     * @param JobStorage $jobStorage
     * @param LoggerInterface $logger
     * @param FileManager $fileManager
     */
    public function __construct(
        CliImportHandler $cliImportHandler,
        JobRunner $jobRunner,
        ImportExportResultSummarizer $importExportResultSummarizer,
        JobStorage $jobStorage,
        LoggerInterface $logger,
        FileManager $fileManager
    ) {
        $this->cliImportHandler = $cliImportHandler;
        $this->jobRunner = $jobRunner;
        $this->importExportResultSummarizer = $importExportResultSummarizer;
        $this->jobStorage = $jobStorage;
        $this->logger = $logger;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset(
            $body['fileName'],
            $body['jobName'],
            $body['processorAlias'],
            $body['jobId'],
            $body['process'],
            $body['originFileName']
        )) {
            $this->logger->critical('Invalid message', ['message' => $body]);

            return self::REJECT;
        }

        $body = array_replace_recursive([
            'options' => []
        ], $body);

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            function (JobRunner $jobRunner, Job $job) use ($body) {
                $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);
                $this->cliImportHandler->setImportingFileName($filePath);
                $result = $this->cliImportHandler->handle(
                    $body['process'],
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['options']
                );
                $this->saveJobResult($job, $result);
                $this->logger->info(
                    $this->importExportResultSummarizer->getSummaryMessage(
                        array_merge(['originFileName' => $body['originFileName']], $result),
                        $body['process'],
                        $this->logger
                    ),
                    ['message' => $body]
                );
                return $result['success'];
            }
        );

        $this->fileManager->deleteFile($body['fileName']);

        return $result ? self::ACK : self::REJECT;
    }

    protected function saveJobResult(Job $job, array $data)
    {
        unset($data['message']);
        unset($data['importInfo']);
        $job = $this->jobStorage->findJobById($job->getId());
        $job->setData($data);
        $this->jobStorage->saveJob($job);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CLI_IMPORT];
    }
}
