<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\EmailBundle\Exception\NotSupportedException;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
     *  @var ImportExportResultSummarizer
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
     * @param ImportExportResultSummarizer $importJobSummaryResultService
     * @param ConfigManager $configManager
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        JobStorage $jobStorage,
        ImportExportResultSummarizer $importJobSummaryResultService,
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
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset($body['rootImportJobId'], $body['process']) &&
            ! (isset($body['userId']) || isset($body['notifyEmail']))
        ) {
            $this->logger->critical('Invalid message', ['message' => $body]);

            return self::REJECT;
        }

        if (! ($job = $this->jobStorage->findJobById($body['rootImportJobId']))) {
            $this->logger->critical('Job not found', ['message' => $body]);

            return self::REJECT;
        }

        if (!isset($body['notifyEmail']) || !$body['notifyEmail']) {
            $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
            if (! $user instanceof User) {
                $this->logger->error(
                    sprintf('User not found. Id: %s', $body['userId']),
                    ['message' => $message]
                );

                return self::REJECT;
            }
            $notifyEmail = $user->getEmail();
        } else {
            $notifyEmail = $body['notifyEmail'];
        }

        switch ($body['process']) {
            case ProcessorRegistry::TYPE_IMPORT_VALIDATION:
                $template = ImportExportResultSummarizer::TEMPLATE_IMPORT_VALIDATION_RESULT;
                break;
            case ProcessorRegistry::TYPE_IMPORT:
                $template = ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT;
                break;
            default:
                throw new NotSupportedException(
                    sprintf(
                        'Not found template for "%s" process of Import',
                        $body['process']
                    )
                );
                break;
        }

//        TODO refactor in https://magecore.atlassian.net/browse/BAP-13215
        list($subject, $summary) = $this->importJobSummaryResultService->getSummaryResultForNotification(
            $job,
            $body['originFileName'],
            $template
        );

        $this->sendNotification($subject, $notifyEmail, $summary);

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
