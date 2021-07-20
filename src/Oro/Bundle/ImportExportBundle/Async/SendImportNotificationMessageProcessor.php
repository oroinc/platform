<?php

namespace Oro\Bundle\ImportExportBundle\Async;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Exception\NotSupportedException;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\NotificationBundle\Async\Topics as NotificationTopics;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Responsible for sending different types of notifications related to import process such as import result and
 * validation result notifications.
 */
class SendImportNotificationMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
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
     * @var NotificationSettings
     */
    private $notificationSettings;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var int
     */
    private $recipientUserId;

    public function __construct(
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        ImportExportResultSummarizer $importJobSummaryResultService,
        NotificationSettings $notificationSettings,
        ManagerRegistry $doctrine
    ) {
        $this->producer = $producer;
        $this->logger = $logger;
        $this->importJobSummaryResultService = $importJobSummaryResultService;
        $this->notificationSettings = $notificationSettings;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (! isset($body['rootImportJobId'], $body['process'], $body['userId'])) {
            $this->logger->critical('Invalid message');

            return self::REJECT;
        }

        if (! ($job = $this->getJobRepository()->findJobById((int)$body['rootImportJobId']))) {
            $this->logger->critical('Job not found');

            return self::REJECT;
        }

        $user = $this->doctrine->getRepository(User::class)->find($body['userId']);
        if (! $user instanceof User) {
            $this->logger->error(
                sprintf('User not found. Id: %s', $body['userId'])
            );

            return self::REJECT;
        }
        $this->recipientUserId = $user->getId();

        switch ($body['process']) {
            case ProcessorRegistry::TYPE_IMPORT_VALIDATION:
                $template = ImportExportResultSummarizer::TEMPLATE_IMPORT_VALIDATION_RESULT;
                break;
            case ProcessorRegistry::TYPE_IMPORT:
                $template = ImportExportResultSummarizer::TEMPLATE_IMPORT_RESULT;
                break;
            default:
                throw new NotSupportedException(
                    sprintf('Not found template for "%s" process of Import', $body['process'])
                );
                break;
        }

        $data = $this->importJobSummaryResultService
            ->getSummaryResultForNotification($job, $body['originFileName']);

        $this->sendNotification($user->getEmail(), $template, $data);

        return self::ACK;
    }

    /**
     * @param string $toEmail
     * @param string $template
     * @param array $body
     */
    protected function sendNotification($toEmail, $template, array $body)
    {
        $sender = $this->notificationSettings->getSender();
        $message = [
            'sender' => $sender->toArray(),
            'toEmail' => $toEmail,
            'body' => $body,
            'contentType' => 'text/html',
            'template' => $template,
        ];

        if ($this->recipientUserId) {
            $message['recipientUserId'] = $this->recipientUserId;
        }

        $this->producer->send(
            NotificationTopics::SEND_NOTIFICATION_EMAIL,
            $message
        );

        $this->logger->info('Sent notification message.');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_IMPORT_NOTIFICATION];
    }

    /**
     * @return JobRepository|EntityRepository
     */
    private function getJobRepository(): JobRepository
    {
        return $this->doctrine->getManagerForClass(Job::class)->getRepository(Job::class);
    }
}
