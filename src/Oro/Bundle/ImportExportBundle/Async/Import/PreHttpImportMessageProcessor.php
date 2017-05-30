<?php
namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topics as NotifcationTopics;
use Oro\Bundle\UserBundle\Entity\User;
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
     * @var DependentJobService
     */
    protected $dependentJob;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var HttpImportHandler
     */
    protected $httpImportHandler;

    /**
     * @var WriterChain
     */
    protected $writerChain;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var integer
     */
    protected $batchSize;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param DependentJobService $dependentJob
     * @param DependentJobService $dependentJob
     * @param FileManager $fileManager
     * @param HttpImportHandler $httpImportHandler
     * @param WriterChain $writerChain
     * @param integer $batchSize
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        DependentJobService $dependentJob,
        FileManager $fileManager,
        HttpImportHandler $httpImportHandler,
        WriterChain $writerChain,
        $batchSize
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
        $this->dependentJob = $dependentJob;
        $this->fileManager = $fileManager;
        $this->httpImportHandler = $httpImportHandler;
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
     * @param ManagerRegistry $managerRegistry
     */
    public function setManagerRegistry(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
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
            $body['securityToken'],
            $body['jobName'],
            $body['process'],
            $body['fileName'],
            $body['originFileName']
        )) {
            $this->logger->critical(
                sprintf('[PreHttpImportMessageProcessor] Got invalid message. body: %s', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $body = array_replace_recursive([
            'processorAlias' => null,
            'options' => [],
        ], $body);

        $body['options']['batch_size'] = $this->batchSize;
        $format = pathinfo($body['originFileName'], PATHINFO_EXTENSION);
        $writer = $this->writerChain->getWriter($format);

        if (! $writer instanceof FileStreamWriter) {
            $this->logger->warning(
                sprintf('[PreHttpImportMessageProcessor] Not supported format: "%s", using default', $format),
                ['message' => $message]
            );
            $writer = $this->writerChain->getWriter('csv');
        }

        if (! ($files = $this->getSplittedFiles($writer, $body))) {
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
                        sprintf('%s:chunk.%s', $jobName, ++$key),
                        function (JobRunner $jobRunner, Job $child) use ($body, $file, $key) {
                            $body['fileName'] = $file;
                            $body['options']['batch_number'] = $key;
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

    /**
     * @param FileStreamWriter $writer
     * @param array $body
     * @return array|null
     */
    private function getSplittedFiles(FileStreamWriter $writer, array $body)
    {
        try {
            $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);
            $this->httpImportHandler->setImportingFileName($filePath);

            return $this->httpImportHandler->splitImportFile(
                $body['jobName'],
                $body['process'],
                $writer
            );
        } catch (\Exception $e) {
            $this->sendErrorNotification($body, $e->getMessage());

            return null;
        } catch (\Throwable $e) {
            $this->sendErrorNotification($body, $e->getMessage());

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRE_HTTP_IMPORT, Topics::IMPORT_HTTP_PREPARING, Topics::IMPORT_HTTP_VALIDATION_PREPARING];
    }

    /**
     * @param array $body
     * @param string $error
     */
    private function sendErrorNotification(array $body, $error)
    {
        $errorMessage = sprintf(
            '[PreHttpImportMessageProcessor] An error occurred while reading file %s: "%s"',
            $body['originFileName'],
            $error
        );

        $this->logger->critical($errorMessage, ['message' => $body]);

        $user = $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getRepository(User::class)
            ->find($body['userId']);

        if (! $user instanceof User) {
            $this->logger->critical(
                sprintf('[PreHttpImportMessageProcessor] User not found. Id: %s', $body['userId']),
                ['body' => $body]
            );

            return;
        }

        $this->producer->send(NotifcationTopics::SEND_NOTIFICATION_EMAIL, [
            'fromEmail' => $this->configManager->get('oro_notification.email_notification_sender_email'),
            'fromName' => $this->configManager->get('oro_notification.email_notification_sender_name'),
            'toEmail' => $user->getEmail(),
            'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_ERROR,
            'body' => [
                'originFileName' => $body['originFileName'],
                'error' => 'The import file could not be imported due to a fatal error. ' .
                           'Please check its integrity and try again!',
            ],
            'contentType' => 'text/html',
        ]);
    }

    /**
     * Method convert body old import topic to new
     * @deprecated (deprecated since 2.1 will be remove in 2.3)
     * @param $message
     */
    private function backwardCompatibilityMapper(MessageInterface $message)
    {
        $topic = $message->getProperty(Config::PARAMETER_TOPIC_NAME);

        if ($topic !== Topics::IMPORT_HTTP_PREPARING && $topic !== Topics::IMPORT_HTTP_VALIDATION_PREPARING) {
            return;
        }
        $body = JSON::decode($message->getBody());

        if (! $body['filePath'] || ! $body['processorAlias'] || ! $body['userId'] || ! $body['securityToken']) {
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
