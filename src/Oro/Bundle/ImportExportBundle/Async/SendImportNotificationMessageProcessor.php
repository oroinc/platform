<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\UserBundle\Entity\User;

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
     *  @var ConsolidateImportJobResultNotificationService
     */
    private $consolidateJobNotificationService;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var RegistryInterface
     */
    private $doctrine;


    public function __construct(
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        JobStorage $jobStorage,
        ConsolidateImportJobResultNotificationService $consolidateJobNotificationService,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        RegistryInterface $doctrine
    ) {
        $this->producer = $producer;
        $this->logger = $logger;
        $this->jobStorage = $jobStorage;
        $this->consolidateJobNotificationService = $consolidateJobNotificationService;
        $this->configManager = $configManager;
        $this->translator = $translator;
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
            $summary = $this->consolidateJobNotificationService->getImportSummary($job, $body['originFileName']);
            $subject = $this->translator->trans(
                'oro.importexport.import.async_import',
                ['%origin_file_name%' => $body['originFileName']]
            );
        } else {
            $summary = $this->consolidateJobNotificationService->getValidationImportSummary(
                $job,
                $body['originFileName']
            );
            $subject = $this->translator->trans(
                'oro.importexport.import.async_validation_import',
                ['%origin_file_name%' => $body['originFileName']]
            );
        }

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
            'body' => $summary
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
