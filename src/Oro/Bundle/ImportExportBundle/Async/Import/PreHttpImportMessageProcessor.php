<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

use Oro\Bundle\ImportExportBundle\Splitter\SplitterChain;
use Oro\Bundle\ImportExportBundle\Splitter\SplitterInterface;
use Oro\Component\MessageQueue\Client\Config;
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

class PreHttpImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SplitterChain
     */
    protected $splitterChain;

    /**
     * @var DependentJobService
     */
    protected $dependentJob;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param SplitterChain $splitterChain
     * @param DependentJobService $dependentJob
     * @param DependentJobService $dependentJob
     * @param FileManager $fileManager
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        SplitterChain $splitterChain,
        DependentJobService $dependentJob,
        FileManager $fileManager
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->splitterChain = $splitterChain;
        $this->dependentJob = $dependentJob;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $this->backwardCompatibilityMapper($message);
        $body = JSON::decode($message->getBody());

        if (! isset(
            $body['userId'],
            $body['jobName'],
            $body['process'],
            $body['fileName'],
            $body['originFileName']
        )) {
            $this->logger->critical(
                sprintf('Got invalid message. body: %s', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $body = array_replace_recursive([
                'processorAlias' => null,
                'options' => [],
            ], $body);

        $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);

        $format = pathinfo($body['originFileName'], PATHINFO_EXTENSION);
        $splitterFile = $this->splitterChain->getSplitter($format);

        if (! $splitterFile instanceof SplitterInterface) {
            $this->logger->warning(
                sprintf('Not supported format: "%s", using default', $format),
                ['message' => $message]
            );
            $splitterFile = $this->splitterChain->getSplitter('default');
        }

        $files = $splitterFile->getSplittedFilesNames($filePath);

        if (! count($files)) {
            $errors = $splitterFile->getErrors();
            $this->sendErrorNotification($body, $errors);

            return self::REJECT;
        }

        $parentMessageId = $message->getMessageId();
        $jobName = sprintf(
            'oro:%s:%s:%s:%s',
            $body['process'],
            $body['processorAlias'],
            $body['jobName'],
            $body['userId']
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
                        function (JobRunner $jobRunner, Job $child) use ($body, $file, $key) {
                            $body['fileName'] = $file;
                            $this->producer->send(
                                Topics::HTTP_IMPORT,
                                array_merge($body, ['jobId' => $child->getId()])
                            );
                        }
                    );
                }
                $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
                $context->addDependentJob(
                    Topics::SEND_IMPORT_NOTIFICATION,
                    [
                        'rootImportJobId' => $job->getRootJob()->getId(),
                        'originFileName' => $body['originFileName'],
                        'userId' => $body['userId'],
                        'process' => $body['process'],
                    ]
                );
                $this->dependentJob->saveDependentJob($context);

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
        $this->producer->send(
            Topics::SEND_IMPORT_ERROR_NOTIFICATION,
            [
                'file' => $body['originFileName'],
                'error' => $errorMessage,
                'userId' => $body['userId'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRE_HTTP_IMPORT, Topics::IMPORT_HTTP_PREPARING, Topics::IMPORT_HTTP_VALIDATION_PREPARING];
    }

    /**
     * Method convert body old import topic to new
     * @deprecated (deprecated since 2.1 will be remove in 2.3)
     * @param $message
     */
    private function backwardCompatibilityMapper(MessageInterface &$message)
    {
        $topic = $message->getProperty(Config::PARAMETER_TOPIC_NAME);

        if ($topic !== Topics::IMPORT_HTTP_PREPARING && $topic !== Topics::IMPORT_HTTP_VALIDATION_PREPARING) {
            return;
        }
        $body = JSON::decode($message->getBody());

        if (! $body['filePath'] || ! $body['processorAlias'] || ! $body['userId']) {
            return;
        }
        $body['fileName'] = FileManager::generateUniqueFileName(pathinfo($body['originFileName'], PATHINFO_EXTENSION));
        $this->fileManager->writeFileToStorage($body['filePath'], $body['fileName']);

        if (Topics::IMPORT_HTTP_PREPARING === $topic) {
            $body['process'] = ProcessorRegistry::TYPE_IMPORT;
            $body['jobName'] = JobExecutor::JOB_IMPORT_FROM_CSV;
        } else {
            $body['process'] = ProcessorRegistry::TYPE_IMPORT_VALIDATION;
            $body['jobName'] = JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV;
        }
        $message->setBody(JSON::encode($body));
    }
}
