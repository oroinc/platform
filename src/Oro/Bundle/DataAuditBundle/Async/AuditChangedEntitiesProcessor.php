<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseRelationsTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesRelationsTopic;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesTopic;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Process inserted, updated and deleted entities and create audit logs
 * Schedule relations processing if needed
 * Schedule inversed relations processing
 */
class AuditChangedEntitiesProcessor extends AbstractAuditProcessor implements TopicSubscriberInterface
{
    private EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter;

    private MessageProducerInterface $messageProducer;

    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    public function __construct(
        EntityChangesToAuditEntryConverter $entityChangesToAuditEntryConverter,
        MessageProducerInterface $messageProducer,
        EntityAuditStrategyProcessorInterface $strategyProcessor
    ) {
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
        $this->messageProducer = $messageProducer;
        $this->strategyProcessor = $strategyProcessor;
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
            if ($body['entities_inserted']) {
                $map = $this->processEntityStrategy($body['entities_inserted']);
                $this->entityChangesToAuditEntryConverter->convert(
                    $map,
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
                $map = $this->processEntityStrategy($body['entities_updated']);
                $this->entityChangesToAuditEntryConverter->convert(
                    $map,
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
                $map = $this->processEntityStrategy($body['entities_deleted']);
                $this->entityChangesToAuditEntryConverter->convert(
                    $map,
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

        if ($body['collections_updated']) {
            $auditChangedEntitiesRelationsTopicBody = $body;
            unset(
                $auditChangedEntitiesRelationsTopicBody['entities_inserted'],
                $auditChangedEntitiesRelationsTopicBody['entities_updated'],
                $auditChangedEntitiesRelationsTopicBody['entities_deleted']
            );
            $this->messageProducer->send(
                AuditChangedEntitiesRelationsTopic::getName(),
                $auditChangedEntitiesRelationsTopicBody
            );
        }
        $this->messageProducer->send(AuditChangedEntitiesInverseRelationsTopic::getName(), $body);
        $this->messageProducer->send(AuditChangedEntitiesInverseCollectionsTopic::getName(), $body);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [AuditChangedEntitiesTopic::getName()];
    }

    private function processEntityStrategy(array $entitiesChanged): array
    {
        $map = [];
        foreach ($entitiesChanged as $key => $entityChanged) {
            $return = $this->strategyProcessor->processChangedEntities($entityChanged);

            if (!empty($return)) {
                $map[$key] = $entityChanged;
            }
        }

        return $map;
    }
}
