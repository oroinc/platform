<?php

namespace Oro\Bundle\IntegrationBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\Topic\ProcessSingleWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Exception\RetryableWebhookDeliveryException;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotificationSenderInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRedeliveryException;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Processes a single webhook notification endpoint.
 */
class ProcessSingleWebhookNotificationProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private JobRunner $jobRunner,
        private EntityManagerInterface $entityManager,
        private WebhookNotificationSenderInterface $notificationSender
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        $delayMs = null;
        try {
            $result = $this->jobRunner->runDelayed(
                $body['job_id'],
                function () use ($body, $message, &$delayMs) {
                    try {
                        $webhook = $this->entityManager->find(
                            WebhookProducerSettings::class,
                            $body['webhook_id']
                        );

                        if (!$webhook) {
                            $this->logger->warning(
                                'Webhook producer settings not found',
                                [
                                    'webhook_id' => $body['webhook_id'],
                                    'message_id' => $body['message_id']
                                ]
                            );

                            return false;
                        }

                        if (!$webhook->isEnabled()) {
                            $this->logger->info(
                                'Webhook is disabled, skipping',
                                [
                                    'webhook_id' => $body['webhook_id'],
                                    'message_id' => $body['message_id']
                                ]
                            );

                            return true;
                        }

                        return $this->processJob($webhook, $body);
                    } catch (RetryableWebhookDeliveryException $e) {
                        $delayMs = $e->getDelay();

                        throw JobRedeliveryException::create();
                    } catch (\Throwable $e) {
                        $this->logger->error(
                            'Failed to process single webhook',
                            [
                                'message_id' => $body['message_id'],
                                'message' => $message->getBody(),
                                'exception' => $e
                            ]
                        );

                        return false;
                    }
                }
            );
        } catch (JobRedeliveryException) {
            $this->logger->warning(
                'Redelivering webhook notification',
                [
                    'webhook_id' => $body['webhook_id'],
                    'message_id' => $body['message_id']
                ]
            );

            if ($delayMs !== null) {
                $message->setDelay(ceil($delayMs / 1000));
            }

            return self::REQUEUE;
        }

        return $result ? self::ACK : self::REJECT;
    }

    private function processJob(WebhookProducerSettings $webhook, array $body): bool
    {
        return $this->notificationSender->send(
            $webhook,
            $body['event_data'],
            $body['timestamp'],
            $body['message_id'],
            $body['metadata'],
            true
        );
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [ProcessSingleWebhookNotificationTopic::getName()];
    }
}
