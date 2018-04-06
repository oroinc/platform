<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class AuditChangedEntitiesRelationsProcessor extends AbstractAuditProcessor implements TopicSubscriberInterface
{
    /** @var EntityChangesToAuditEntryConverter */
    private $entityChangesToAuditEntryConverter;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        LoggerInterface $logger
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (empty($body['collections_updated'])) {
            // it seems that a producer sent unnecessary message
            $this->logger->warning('The "collections_updated" should not be empty.');

            return self::REJECT;
        }

        $loggedAt = $this->getLoggedAt($body);
        $transactionId = $this->getTransactionId($body);
        $user = $this->getUserReference($body);
        $organization = $this->getOrganizationReference($body);
        $impersonation = $this->getImpersonationReference($body);
        $ownerDescription = $this->getOwnerDescription($body);

        $this->entityChangesToAuditEntryConverter->convert(
            $body['collections_updated'],
            $transactionId,
            $loggedAt,
            $user,
            $organization,
            $impersonation,
            $ownerDescription
        );

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ENTITIES_RELATIONS_CHANGED];
    }
}
