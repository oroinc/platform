<?php
namespace Oro\Bundle\ImportExportBundle\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\NotificationBundle\Async\Topics as EmailTopics;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ExportMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ExportHandler
     */
    private $exportHandler;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ExportHandler $exportHandler
     * @param JobRunner $jobRunner
     * @param MessageProducerInterface $producer
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExportHandler $exportHandler,
        JobRunner $jobRunner,
        MessageProducerInterface $producer,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        LoggerInterface $logger
    ) {
        $this->exportHandler = $exportHandler;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'jobName' => null,
            'processorAlias' => null,
            'userId' => null,
            'exportType' => ProcessorRegistry::TYPE_EXPORT,
            'outputFormat' => 'csv',
            'outputFilePrefix' => null,
            'options' => [],
        ], $body);

        if (! isset($body['jobName'], $body['processorAlias'], $body['userId'])) {
            $this->logger->critical(
                sprintf('[ExportMessageProcessor] Got invalid message: "%s"', $message->getBody()),
                ['message' => $message]
            );

            return self::REJECT;
        }

        /** @var User $user */
        $user = $this->doctrineHelper->getEntityRepository(User::class)->find($body['userId']);
        if (! $user) {
            $this->logger->critical(
                sprintf('[ExportMessageProcessor] Cannot find user by id "%s"', $body['userId']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $jobUniqueName = Topics::EXPORT . '_' . $body['processorAlias'];

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobUniqueName,
            function () use ($body, $jobUniqueName, $user) {
                $exportResult = $this->exportHandler->getExportResult(
                    $body['jobName'],
                    $body['processorAlias'],
                    $body['exportType'],
                    $body['outputFormat'],
                    $body['outputFilePrefix'],
                    $body['options']
                );

                $this->sendNotificationMessage($jobUniqueName, $exportResult, $user);

                $this->logger->info(sprintf(
                    '[ExportMessageProcessor] Export result. Success: %s. ReadsCount: %s. ErrorsCount: %s',
                    $exportResult['success'],
                    $exportResult['readsCount'],
                    $exportResult['errorsCount']
                ));

                return $exportResult['success'];
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * Send async email notification message with export result.
     *
     * @param string $jobUniqueName
     * @param array $exportResult
     * @param User $user
     */
    protected function sendNotificationMessage($jobUniqueName, array $exportResult, $user)
    {
        $subject = sprintf('Export result for job %s', $jobUniqueName);

        if ($exportResult['success']) {
            if ($exportResult['readsCount']) {
                $body = sprintf(
                    'Export performed successfully, %s %s were exported. Download link: %s',
                    $exportResult['readsCount'],
                    $exportResult['entities'],
                    $exportResult['url']
                );
            } else {
                $body = sprintf('No %s found for export.', $exportResult['entities']);
            }
        } else {
            $body = sprintf(
                'Export operation fails, %s error(s) found. Error log: %s',
                $exportResult['errorsCount'],
                $exportResult['url']
            );
        }

        $this->producer->send(EmailTopics::SEND_NOTIFICATION_EMAIL, [
            'fromEmail' => $this->configManager->get('oro_notification.email_notification_sender_email'),
            'fromName' => $this->configManager->get('oro_notification.email_notification_sender_name'),
            'toEmail' => $user->getEmail(),
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT];
    }
}
