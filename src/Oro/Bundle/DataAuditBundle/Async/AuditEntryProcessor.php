<?php

namespace Oro\Bundle\DataAuditBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditEntryTopic;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Service\SetNewAuditVersionService;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Stores a Data Audit entry from the payload published by
 * {@see \Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder}.
 */
class AuditEntryProcessor extends AbstractAuditProcessor implements TopicSubscriberInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SetNewAuditVersionService $setNewAuditVersionService
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        if (empty($body['changes'])) {
            return self::ACK;
        }

        $audit = new Audit();
        $audit->setObjectClass((string)$body['object_class']);
        $audit->setObjectId((string)$body['object_id']);
        $audit->setObjectName((string)$body['object_name']);
        $audit->setAction((string)$body['action']);
        $audit->setLoggedAt($this->getLoggedAt($body));
        $audit->setTransactionId($this->getTransactionId($body));
        $audit->setOwnerDescription($this->getOwnerDescription($body));

        $audit->setUser($this->getEntity($this->getUserReference($body), User::class));
        $audit->setOrganization($this->getEntity($this->getOrganizationReference($body), Organization::class));
        $audit->setImpersonation($this->getEntity($this->getImpersonationReference($body), Impersonation::class));

        foreach ($body['changes'] as $change) {
            $audit->addField(new AuditField(
                (string)$change['field'],
                (string)($change['type'] ?? AuditFieldTypeRegistry::TYPE_TEXT),
                $change['new'] ?? null,
                $change['old'] ?? null
            ));
        }

        $em = $this->doctrine->getManagerForClass(Audit::class);
        $em->persist($audit);
        $em->flush();

        $this->setNewAuditVersionService->setVersion($audit);

        return self::ACK;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [AuditEntryTopic::getName()];
    }

    private function getEntity(EntityReference $reference, string $expectedClass): ?object
    {
        $class = $reference->getClassName();
        $entity = $class ? $this->doctrine->getManagerForClass($class)?->find($class, $reference->getId()) : null;

        return $entity instanceof $expectedClass ? $entity : null;
    }
}
