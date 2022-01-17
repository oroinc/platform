<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesRelationsTopic;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Processed collections for relations.
 */
class AuditChangedEntitiesRelationsProcessor extends AbstractAuditProcessor implements TopicSubscriberInterface
{
    private EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter;

    public function __construct(
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = $message->getBody();
        $loggedAt = $this->getLoggedAt($body);
        $transactionId = $this->getTransactionId($body);
        $user = $this->getUserReference($body);
        $organization = $this->getOrganizationReference($body);
        $impersonation = $this->getImpersonationReference($body);
        $ownerDescription = $this->getOwnerDescription($body);

        try {
            $this->entityChangesToAuditEntryConverter->convert(
                $body['collections_updated'],
                $transactionId,
                $loggedAt,
                $user,
                $organization,
                $impersonation,
                $ownerDescription
            );
        } catch (WrongDataAuditEntryStateException $e) {
            return self::REQUEUE;
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [AuditChangedEntitiesRelationsTopic::getName()];
    }
}
