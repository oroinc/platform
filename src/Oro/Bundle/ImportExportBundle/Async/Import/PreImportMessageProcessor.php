<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTemplateTopic;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for splitting import process into a set of independent jobs each processing its own
 * chunk of data to be run in parallel. Notifies user by email if import error occurs.
 */
class PreImportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @var DependentJobService
     */
    protected $dependentJob;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var ImportHandler
     */
    protected $importHandler;

    /**
     * @var WriterChain
     */
    protected $writerChain;

    /**
     * @var NotificationSettings
     */
    protected $notificationSettings;

    /**
     * @var int
     */
    protected $batchSize;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param DependentJobService $dependentJob
     * @param FileManager $fileManager
     * @param ImportHandler $importHandler
     * @param WriterChain $writerChain
     * @param NotificationSettings $notificationSettings
     * @param ManagerRegistry $managerRegistry
     * @param EventDispatcherInterface $eventDispatcher
     * @param int $batchSize
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        DependentJobService $dependentJob,
        FileManager $fileManager,
        ImportHandler $importHandler,
        WriterChain $writerChain,
        NotificationSettings $notificationSettings,
        ManagerRegistry $managerRegistry,
        EventDispatcherInterface $eventDispatcher,
        $batchSize
    ) {
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->dependentJob = $dependentJob;
        $this->fileManager = $fileManager;
        $this->importHandler = $importHandler;
        $this->writerChain = $writerChain;
        $this->notificationSettings = $notificationSettings;
        $this->managerRegistry = $managerRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [PreImportTopic::getName()];
    }

    /**
     * @param MessageInterface $message
     * @param array $body
     * @param array $files
     *
     * @return mixed
     */
    protected function processJob(MessageInterface $message, array $body, array $files)
    {
        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function (JobRunner $jobRunner, Job $job) use ($body, $files) {
                $body['options']['importVersion'] = time();
                $this->dispatchBeforeChunksEvent($body);
                $this->createFinishJobs($job, $body);

                $jobName = $job->getName();
                foreach ($files as $key => $file) {
                    $jobRunner->createDelayed(
                        sprintf('%s:chunk.%s', $jobName, ++$key),
                        function (JobRunner $jobRunner, Job $child) use ($body, $file, $key) {
                            $body['fileName'] = $file;
                            $body['options']['batch_number'] = $key;
                            $this->producer->send(
                                ImportTopic::getName(),
                                array_merge($body, ['jobId' => $child->getId()])
                            );
                        }
                    );
                }

                return true;
            }
        );
        $this->fileManager->deleteFile($body['fileName']);

        return $result;
    }

    private function createFinishJobs(Job $job, array $body): void
    {
        $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
        $context->addDependentJob(
            SendImportNotificationTopic::getName(),
            [
                'rootImportJobId' => $job->getRootJob()->getId(),
                'originFileName' => $body['originFileName'],
                'userId' => $body['userId'],
                'process' => $body['process'],
            ]
        );
        $context->addDependentJob(SaveImportExportResultTopic::getName(), [
            'jobId' => $job->getRootJob()->getId(),
            'userId' => $body['userId'],
            'type' => $body['process'],
            'entity' => $this->importHandler->getEntityName(
                $body['process'],
                $body['processorAlias']
            ),
            'options' => $body['options']
        ]);
        $this->dependentJob->saveDependentJob($context);
    }

    private function sendErrorNotification(array $body, string $error): void
    {
        $errorMessage = sprintf(
            'An error occurred while reading file %s: "%s"',
            $body['originFileName'],
            $error
        );

        $this->logger->critical($errorMessage);

        $user = $this->managerRegistry
            ->getRepository(User::class)
            ->find($body['userId']);

        if (! $user instanceof User) {
            $this->logger->critical(
                sprintf('User not found. Id: %s', $body['userId'])
            );

            return;
        }

        $message = [
            'from' => $this->notificationSettings->getSender()->toString(),
            'recipientUserId' => $user->getId(),
            'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_ERROR,
            'templateParams' => [
                'originFileName' => $body['originFileName'],
                'error' => 'The import file could not be imported due to a fatal error. ' .
                    'Please check its integrity and try again!',
            ],
        ];

        $this->producer->send(SendEmailNotificationTemplateTopic::getName(), $message);
    }

    /**
     * @param array $body
     *
     * @return array|bool
     */
    protected function getFiles(array $body)
    {
        $format = pathinfo($body['originFileName'], PATHINFO_EXTENSION);
        $writer = $this->writerChain->getWriter($format);

        if (!$writer instanceof FileStreamWriter) {
            $this->logger->warning(
                sprintf('Not supported format: "%s", using default', $format)
            );
            $writer = $this->writerChain->getWriter('csv');
        }

        try {
            $filePath = $this->fileManager->writeToTmpLocalStorage($body['fileName']);

            $this->importHandler->setImportingFileName($filePath);
            $this->importHandler->setConfigurationOptions($body['options']);

            $files = $this->importHandler->splitImportFile(
                $body['jobName'],
                $body['process'],
                $writer
            );
        } catch (\Exception $e) {
            $this->sendErrorNotification($body, $e->getMessage());

            return false;
        } catch (\Throwable $e) {
            $this->sendErrorNotification($body, $e->getMessage());

            return false;
        } finally {
            @unlink($filePath);
        }

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();

        $files = $this->getFiles($messageBody);

        if (!$files) {
            return self::REJECT;
        }

        $result = $this->processJob($message, $messageBody, $files);

        return $result ? self::ACK : self::REJECT;
    }

    protected function dispatchBeforeChunksEvent(array $body): void
    {
        $this->eventDispatcher?->dispatch(
            new BeforeImportChunksEvent($body),
            Events::BEFORE_CREATING_IMPORT_CHUNK_JOBS
        );
    }
}
