<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\MessageQueue\Job\JobStorage;

class SendImportNotificationMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     *  @var LoggerInterface
     */
    private $logger;

    /**
     *  @var ImportExportJobSummaryResultService
     */
    private $importJobSummaryResultService;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var RegistryInterface
     */
    private $doctrine;


    /**
     * @param MessageProducerInterface $producer
     * @param LoggerInterface $logger
     * @param JobStorage $jobStorage
     * @param ImportExportJobSummaryResultService $importJobSummaryResultService
     * @param ConfigManager $configManager
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        JobStorage $jobStorage,
        ImportExportJobSummaryResultService $importJobSummaryResultService,
        ConfigManager $configManager,
        RegistryInterface $doctrine
    ) {
        $this->producer = $producer;
        $this->logger = $logger;
        $this->jobStorage = $jobStorage;
        $this->importJobSummaryResultService = $importJobSummaryResultService;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    /**
     * @var array
     */
    protected static $stopStatuses = [Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED];

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (isset($body['errorMessage'])) {
            $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
            $this->sendNotification(
                'Error importing file ' . $body['originFileName'],
                $user->getEmail(),
                'The import resulted in the following error: ' . $body['errorMessage']
            );
            return self::REJECT;
        }

        if (!isset($body['rootImportJobId'])) {
            $this->logger->critical('Invalid message', ['message' => $body]);

            return self::REJECT;
        }

        if (! ($job = $this->jobStorage->findJobById($body['rootImportJobId']))) {
            $this->logger->critical('Job not found', ['message' => $body]);

            return self::REJECT;
        }

        $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
        if (! $user instanceof User) {
            $this->logger->error(
                sprintf('User not found. Id: %s', $body['userId']),
                ['message' => $message]
            );

            return self::REJECT;
        }
        if (in_array(Topics::IMPORT_HTTP_PREPARING, $body['subscribedTopic'])) {
            $typeOfResult = ImportExportJobSummaryResultService::TEMPLATE_IMPORT_RESULT;
        } else {
            $typeOfResult = ImportExportJobSummaryResultService::TEMPLATE_IMPORT_VALIDATION_RESULT;
        }

        list($subject, $summary) = $this->importJobSummaryResultService->getSummaryResultForNotification(
            $job,
            $body['originFileName'],
            $typeOfResult
        );

        $this->sendNotification($subject, $user->getEmail(), $summary);

        return self::ACK;
    }

    protected function sendNotification($subject, $toEmail, $summary)
    {
        $fromEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $fromName = $this->configManager->get('oro_notification.email_notification_sender_name');
        $message = [
            'fromEmail' => $fromEmail,
            'fromName' => $fromName,
            'toEmail' => $toEmail,
            'subject' => $subject,
            'body' => $summary,
            'contentType' => 'text/html'
        ];

        $this->producer->send(
            NotificationTopics::SEND_NOTIFICATION_EMAIL,
            $message
        );

        $this->logger->info('Sent notification message.', ['message' => $message]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_IMPORT_NOTIFICATION];
    }
}
