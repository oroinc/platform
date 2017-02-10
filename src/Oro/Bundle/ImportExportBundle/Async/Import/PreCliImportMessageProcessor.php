<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Splitter\SplitterChain;
use Oro\Bundle\ImportExportBundle\Splitter\SplitterInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class PreCliImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DependentJobService
     */
    protected $dependentJob;

    /**
     * @var SplitterChain
     */
    protected $splitterChain;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param DependentJobService $dependentJob
     * @param SplitterChain $splitterChain
     * @param FileManager $fileManager
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        DependentJobService $dependentJob,
        SplitterChain $splitterChain,
        FileManager $fileManager
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->splitterChain = $splitterChain;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset(
            $body['jobName'],
            $body['process'],
            $body['processorAlias'],
            $body['fileName'],
            $body['originFileName']
        )) {
            $this->logger->critical(
                sprintf('Got invalid message. body: %s', $message->getBody()),
                ['message' => $body]
            );

            return self::REJECT;
        }

        $body = array_replace_recursive([
                'notifyEmail' => null,
                'options' => []
            ], $body);

        $format = pathinfo($body['originFileName'], PATHINFO_EXTENSION);
        $splitterFile = $this->splitterChain->getSplitter($format);

        if (! $splitterFile instanceof SplitterInterface) {
            $this->logger->critical(
                sprintf('Not supported format: "%s"', $format),
                ['message' => $message]
            );
            return self::REJECT;
        }
        $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);
        $files = $splitterFile->getSplittedFilesNames($filePath);

        if (! count($files)) {
            $errors = $splitterFile->getErrors();
            $this->sendErrorNotification($body, $errors);

            return self::REJECT;
        }

        $parentMessageId = $message->getMessageId();
        $jobName = sprintf(
            'oro_cli:%s:%s:%s:%s',
            $body['process'],
            $body['processorAlias'],
            $body['jobName'],
            $body['notifyEmail']
        );

        $result = $this->jobRunner->runUnique(
            $parentMessageId,
            $jobName,
            function (JobRunner $jobRunner, Job $job) use ($jobName, $body, $files) {
                foreach ($files as $key => $file) {
                    $jobRunner->createDelayed(
                        sprintf(
                            '%s:chunk.%s',
                            $jobName,
                            ++$key
                        ),
                        function (JobRunner $jobRunner, Job $child) use ($file, $body) {
                            $body['fileName'] = $file;
                            $this->producer->send(
                                Topics::CLI_IMPORT,
                                array_merge($body, ['jobId' => $child->getId()])
                            );
                        }
                    );
                }
                if ($body['notifyEmail']) {
                    $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
                    $context->addDependentJob(
                        Topics::SEND_IMPORT_NOTIFICATION,
                        [
                            'rootImportJobId' => $job->getRootJob()->getId(),
                            'originFileName' => $body['originFileName'],
                            'notifyEmail' => $body['notifyEmail'],
                            'process' => $body['process'],
                        ]
                    );
                    $this->dependentJob->saveDependentJob($context);
                }

                return true;
            }
        );

        $this->fileManager->deleteFile($body['fileName']);

        return $result ? self::ACK : self::REJECT;
    }


    private function sendErrorNotification($body, $errors)
    {
        $errorMessage = sprintf(
            'An error occurred while reading file %s: "%s"',
            $body['originFileName'],
            implode(PHP_EOL, $errors)
        );
        $this->logger->critical($errorMessage, ['message' => $body]);

        if (isset($body['notifyEmail'])) {
            $this->producer->send(
                Topics::SEND_IMPORT_ERROR_NOTIFICATION,
                [
                    'file' => $body['originFileName'],
                    'error' => $errorMessage,
                    'notifyEmail' => $body['notifyEmail'],
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRE_CLI_IMPORT];
    }
}
