<?php

namespace Oro\Bundle\ImportExportBundle\Async\Export;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Base abstract export message processor.
 */
abstract class ExportMessageProcessorAbstract implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FileManager
     */
    protected $fileManager;

    public function __construct(
        JobRunner $jobRunner,
        FileManager $fileManager,
        LoggerInterface $logger
    ) {
        $this->jobRunner = $jobRunner;
        $this->fileManager = $fileManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (! ($body = $this->getMessageBody($message))) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runDelayed(
            $body['jobId'],
            $this->getRunDelayedJobCallback($body)
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param array $body
     *
     * @return \Closure
     */
    protected function getRunDelayedJobCallback(array $body)
    {
        return function (JobRunner $jobRunner, Job $job) use ($body) {
            $exportResult = $this->handleExport($body);

            $this->logger->info(sprintf(
                'Export result. Success: %s. ReadsCount: %s. ErrorsCount: %s',
                $exportResult['success'] ? 'Yes' : 'No',
                $exportResult['readsCount'],
                $exportResult['errorsCount']
            ));

            $this->saveJobResult($job, $exportResult);

            return $exportResult['success'];
        };
    }

    protected function saveJobResult(Job $job, array $data): void
    {
        if (!empty($data['errors'])) {
            $errorLogFile = $this->saveToStorageErrorLog($data['errors']);
            if ($errorLogFile) {
                $data['errorLogFile'] = $errorLogFile;
            }
        }

        $job->setData($data);
    }

    protected function saveToStorageErrorLog(array $errors): string
    {
        $fileName = str_replace('.', '', uniqid('export', true)) . '.json';

        $this->fileManager->writeToStorage(json_encode($errors), $fileName);

        return $fileName;
    }

    /**
     * @param array $body
     *
     * @return array
     */
    abstract protected function handleExport(array $body);

    /**
     * @param MessageInterface $message
     *
     * @return array|bool
     */
    abstract protected function getMessageBody(MessageInterface $message);
}
