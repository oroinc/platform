<?php

namespace Oro\Bundle\IntegrationBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Async\Topic\ProcessSingleWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Async\Topic\SendWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Repository\WebhookProducerSettingsRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Processes webhook notification messages by creating child jobs for each endpoint.
 */
class WebhookNotificationProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private ManagerRegistry $registry,
        private MessageProducerInterface $messageProducer,
        private JobRunner $jobRunner,
        private TokenStorageInterface $tokenStorage,
        private AuthorizationCheckerInterface $authorizationChecker,
        private UsernamePasswordOrganizationTokenFactoryInterface $tokenFactory
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $originalToken = $this->tokenStorage->getToken();
        try {
            $body = $message->getBody();
            if (empty($body['event_data'])) {
                $this->logger->warning(
                    'Webhook notification has no event data',
                    [
                        'topic' => $body['topic'],
                        'entity_class' => $body['entity_class'],
                        'entity_id' => $body['entity_id'],
                        'message_id' => $body['message_id']
                    ]
                );

                return self::REJECT;
            }

            $aclEntity = null;
            if (!empty($body['entity_class'])) {
                $aclEntity = $this->getAclEntity($body);
            }

            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function (JobRunner $jobRunner, Job $job) use ($body, $aclEntity) {
                    $webhooks = $this->getWebhooks($body['topic'], $body, $aclEntity);

                    // Create a child job for each webhook endpoint
                    foreach ($webhooks as $webhook) {
                        $jobRunner->createDelayed(
                            sprintf(
                                '%s:webhook:%s',
                                $job->getName(),
                                $webhook->getId()
                            ),
                            function (JobRunner $jobRunner, Job $child) use ($webhook, $body, $aclEntity) {
                                // Send an MQ message to process a single webhook
                                $metadata = [];
                                if (!empty($body['entity_class'])) {
                                    $metadata['entity_class'] = $body['entity_class'];
                                    $metadata['entity_id'] = $body['entity_id'];
                                }

                                $this->messageProducer->send(
                                    ProcessSingleWebhookNotificationTopic::getName(),
                                    [
                                        'webhook_id' => $webhook->getId(),
                                        'message_id' => $body['message_id'],
                                        'event_data' => $body['event_data'],
                                        'timestamp' => $body['timestamp'],
                                        'job_id' => $child->getId(),
                                        'metadata' => $metadata
                                    ]
                                );

                                return true;
                            }
                        );
                    }

                    return true;
                }
            );

            return $result ? self::ACK : self::REJECT;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to process webhook notification message',
                [
                    'message' => $message->getBody(),
                    'exception' => $e
                ]
            );

            return self::REJECT;
        } finally {
            $this->tokenStorage->setToken($originalToken);
        }
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [SendWebhookNotificationTopic::getName()];
    }

    private function getAclEntity(array $body): object
    {
        $entityClass = $body['entity_class'];
        $aclEntity = $this->registry->getRepository($entityClass)->find($body['entity_id']);
        if ($aclEntity) {
            return $aclEntity;
        }

        // The entity is already removed, so the permissions are checked by its class and ownership.
        // An unknown owner is not associated with any user, business unit or organization,
        // so such an entity stays accessible only on the global and system access levels.
        return new DomainObjectReference(
            $entityClass,
            $body['entity_id'],
            $body['entity_owner_id'],
            $body['entity_organization_id']
        );
    }

    /**
     * Get all active webhooks for this topic
     */
    private function getWebhooks(string $topic, array $body, ?object $aclEntity): array
    {
        /** @var WebhookProducerSettingsRepository $repository */
        $repository = $this->registry->getRepository(WebhookProducerSettings::class);
        $webhooks = $repository->getActiveWebhooks($topic);
        $webhooks = array_filter(
            $webhooks,
            function (WebhookProducerSettings $webhook) use ($aclEntity, $body) {
                if (!$this->isTargetEntityViewAllowed($webhook, $aclEntity)) {
                    $this->logger->info(
                        'Webhook ID {webhook_id} was skipped because of insufficient permissions',
                        [
                            'webhook_id' => $webhook->getId(),
                            'topic' => $body['topic'],
                            'message_id' => $body['message_id']
                        ]
                    );

                    return false;
                }

                return true;
            }
        );

        if (empty($webhooks)) {
            $this->logger->info(
                'No applicable active webhooks found for the given topic',
                [
                    'topic' => $body['topic'],
                    'event_data' => $body['event_data'],
                    'message_id' => $body['message_id']
                ]
            );
        }

        return $webhooks;
    }

    private function isTargetEntityViewAllowed(
        WebhookProducerSettings $webhook,
        ?object $aclEntity,
    ): bool {
        if (!$aclEntity) {
            return true;
        }

        $webhookOwner = $webhook->getOwner();
        $webhookOrganization = $webhook->getOrganization();
        if (!$webhookOwner || !$webhookOrganization) {
            $this->logger->warning(
                'Webhook ID {webhook_id} has invalid ownership data',
                [
                    'webhook_id' => $webhook->getId()
                ]
            );

            return false;
        }

        $oldToken = $this->tokenStorage->getToken();
        $newToken = $this->tokenFactory->create(
            $webhookOwner,
            'main',
            $webhookOrganization,
            $webhookOwner->getUserRoles()
        );

        $this->tokenStorage->setToken($newToken);
        $result = $this->authorizationChecker->isGranted('VIEW', $aclEntity);
        $this->tokenStorage->setToken($oldToken);

        return $result;
    }
}
