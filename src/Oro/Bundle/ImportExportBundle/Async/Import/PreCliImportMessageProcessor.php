<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topics as NotifcationTopics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

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
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var CliImportHandler
     */
    protected $cliImportHandler;

    /**
     * @var WriterChain
     */
    protected $writerChain;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var integer
     */
    protected $batchSize;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param DependentJobService $dependentJob
     * @param FileManager $fileManager
     * @param CliImportHandler $cliImportHandler
     * @param WriterChain $writerChain
     * @param integer $batchSize
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        DependentJobService $dependentJob,
        FileManager $fileManager,
        CliImportHandler $cliImportHandler,
        WriterChain $writerChain,
        $batchSize
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->fileManager = $fileManager;
        $this->cliImportHandler = $cliImportHandler;
        $this->writerChain = $writerChain;
        $this->batchSize = $batchSize;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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

        $body = array_replace_recursive(['notifyEmail' => null, 'options' => []], $body);

        $format = pathinfo($body['originFileName'], PATHINFO_EXTENSION);
        $writer = $this->writerChain->getWriter($format);
        $body['options']['batch_size'] = $this->batchSize;

        if (! $writer instanceof FileStreamWriter) {
            $this->logger->warning(
                sprintf('Not supported format: "%s", using default', $format),
                ['message' => $message]
            );
            $writer = $this->writerChain->getWriter('csv');
        }

        try {
            $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);
            $this->cliImportHandler->setImportingFileName($filePath);
            $files = $this->cliImportHandler->splitImportFile(
                $body['jobName'],
                $body['process'],
                $writer
            );
        } catch (\Exception $e) {
            $this->sendErrorNotification($body, $e->getMessage());

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
                        sprintf('%s:chunk.%s', $jobName, ++$key),
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

    /**
     * @param array $body
     * @param string $error
     */
    private function sendErrorNotification(array $body, $error)
    {
        $errorMessage = sprintf(
            '[PreCliImportMessageProcessor] An error occurred while reading file %s: "%s"',
            $body['originFileName'],
            $error
        );

        $this->logger->critical($errorMessage, ['message' => $body]);

        if (isset($body['notifyEmail'])) {
            $this->producer->send(NotifcationTopics::SEND_NOTIFICATION_EMAIL, [
                'fromEmail' => $this->configManager->get('oro_notification.email_notification_sender_email'),
                'fromName' => $this->configManager->get('oro_notification.email_notification_sender_name'),
                'toEmail' => $body['notifyEmail'],
                'template' => 'import_error',
                'body' => [
                    'originFileName' => $body['originFileName'],
                    'error' => 'The import file could not be imported due to a fatal error. ' .
                               'Please check its integrity and try again!',
                ],
                'contentType' => 'text/html',
            ]);
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
