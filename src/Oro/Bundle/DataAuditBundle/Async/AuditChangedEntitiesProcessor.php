<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Process inserted, updated and deleted entities and create audit logs
 * Schedule relations processing if needed
 * Schedule inversed relations processing
 */
class AuditChangedEntitiesProcessor extends AbstractAuditProcessor implements TopicSubscriberInterface
{
    /** @var EntityChangesToAuditEntryConverter */
    private $entityChangesToAuditEntryConverter;

    /** @var MessageProducerInterface */
    private $messageProducer;

    public function __construct(
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        MessageProducerInterface $messageProducer
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $loggedAt = $this->getLoggedAt($body);
        $transactionId = $this->getTransactionId($body);
        $user = $this->getUserReference($body);
        $organization = $this->getOrganizationReference($body);
        $impersonation = $this->getImpersonationReference($body);
        $ownerDescription = $this->getOwnerDescription($body);

        try {
            if ($body['entities_inserted']) {
                $this->entityChangesToAuditEntryConverter->convert(
                    $body['entities_inserted'],
                    $transactionId,
                    $loggedAt,
                    $user,
                    $organization,
                    $impersonation,
                    $ownerDescription,
                    AbstractAudit::ACTION_CREATE
                );
            }

            if ($body['entities_updated']) {
                $this->entityChangesToAuditEntryConverter->convert(
                    $body['entities_updated'],
                    $transactionId,
                    $loggedAt,
                    $user,
                    $organization,
                    $impersonation,
                    $ownerDescription,
                    AbstractAudit::ACTION_UPDATE
                );
            }

            if ($body['entities_deleted']) {
                $this->entityChangesToAuditEntryConverter->convert(
                    $body['entities_deleted'],
                    $transactionId,
                    $loggedAt,
                    $user,
                    $organization,
                    $impersonation,
                    $ownerDescription,
                    AbstractAudit::ACTION_REMOVE
                );
            }
        } catch (WrongDataAuditEntryStateException $e) {
            return self::REQUEUE;
        }

        $nextMessage = new Message($body, MessagePriority::VERY_LOW);
        if ($body['collections_updated']) {
            $this->messageProducer->send(Topics::ENTITIES_RELATIONS_CHANGED, $nextMessage);
        }
        $this->messageProducer->send(Topics::ENTITIES_INVERSED_RELATIONS_CHANGED, $nextMessage);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ENTITIES_CHANGED];
    }
}
