<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\BeforeImportChunksEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\NotificationBundle\Async\Topics as NotifcationTopics;
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
use Oro\Component\MessageQueue\Util\JSON;
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
     * @var integer
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
     * @param integer $batchSize
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
        // Topic PRE_HTTP_IMPORT subscribed only for possibility to process already existing messages in message queue
        // This is possible after the upgrade to the new application version
        return [Topics::PRE_IMPORT, Topics::PRE_HTTP_IMPORT];
    }

    /**
     * @param array $body
     *
     * @return array|bool
     */
    protected function validateMessageBody($body)
    {
        if (! isset(
            $body['userId'],
            $body['jobName'],
            $body['process'],
            $body['processorAlias'],
            $body['fileName'],
            $body['originFileName']
        )
        ) {
            $this->logger->critical('Got invalid message');

            return false;
        }

        $body = array_replace_recursive([
            'options' => []
        ], $body);

        $body['options'][Context::OPTION_BATCH_SIZE] = $this->batchSize;

        return $body;
    }

    /**
     * @param string $parentMessageId
     * @param array $body
     * @param array $files
     *
     * @return mixed
     */
    protected function processJob(string $parentMessageId, array $body, array $files)
    {
        $jobName = sprintf(
            'oro:%s:%s:%s:%s:%d',
            $body['process'],
            $body['processorAlias'],
            $body['jobName'],
            $body['userId'],
            random_int(1, PHP_INT_MAX)
        );

        $result = $this->jobRunner->runUnique(
            $parentMessageId,
            $jobName,
            function (JobRunner $jobRunner, Job $job) use ($jobName, $body, $files) {
                $body['options']['importVersion'] = time();
                $this->dispatchBeforeChunksEvent($body);
                $this->createFinishJobs($job, $body);

                foreach ($files as $key => $file) {
                    $jobRunner->createDelayed(
                        sprintf('%s:chunk.%s', $jobName, ++$key),
                        function (JobRunner $jobRunner, Job $child) use ($body, $file, $key) {
                            $body['fileName'] = $file;
                            $body['options']['batch_number'] = $key;
                            $this->producer->send(
                                Topics::IMPORT,
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
            Topics::SEND_IMPORT_NOTIFICATION,
            [
                'rootImportJobId' => $job->getRootJob()->getId(),
                'originFileName' => $body['originFileName'],
                'userId' => $body['userId'],
                'process' => $body['process'],
            ]
        );
        $context->addDependentJob(Topics::SAVE_IMPORT_EXPORT_RESULT, [
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

    protected function sendErrorNotification(array $body, string $error): void
    {
        $errorMessage = sprintf(
            'An error occurred while reading file %s: "%s"',
            $body['originFileName'],
            $error
        );

        $this->logger->critical($errorMessage);

        $user = $this->managerRegistry
            ->getManagerForClass(User::class)
            ->getRepository(User::class)
            ->find($body['userId']);

        if (! $user instanceof User) {
            $this->logger->critical(
                sprintf('User not found. Id: %s', $body['userId'])
            );

            return;
        }

        $sender = $this->notificationSettings->getSender();
        $this->producer->send(NotifcationTopics::SEND_NOTIFICATION_EMAIL, [
            'sender' => $sender->toArray(),
            'toEmail' => $user->getEmail(),
            'template' => ImportExportResultSummarizer::TEMPLATE_IMPORT_ERROR,
            'recipientUserId' => $user->getId(),
            'body' => [
                'originFileName' => $body['originFileName'],
                'error' =>  'The import file could not be imported due to a fatal error. ' .
                    'Please check its integrity and try again!',
            ],
            'contentType' => 'text/html',
        ]);
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
        $body = JSON::decode($message->getBody());

        $body = $this->validateMessageBody($body);

        if (! $body) {
            return self::REJECT;
        }

        $files = $this->getFiles($body);

        if (!$files) {
            return self::REJECT;
        }

        $parentMessageId = $message->getMessageId();

        $result = $this->processJob($parentMessageId, $body, $files);

        return $result ? self::ACK : self::REJECT;
    }

    protected function dispatchBeforeChunksEvent(array $body)
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                new BeforeImportChunksEvent($body),
                Events::BEFORE_CREATING_IMPORT_CHUNK_JOBS
            );
        }
    }
}
