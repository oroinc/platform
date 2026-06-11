<?php

namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Exception\WrongDataAuditEntryStateException;
use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;

/**
 * This converter is intended to add the data audit records to the database
 * based on a list of entity changes.
 * @see \Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener
 */
class EntityChangesToAuditEntryConverter
{
    private array $entityMetadataCache = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly AuditEntityMapper $auditEntityMapper,
        private readonly AuditConfigProvider $configProvider,
        private readonly EntityNameProvider $entityNameProvider,
        private readonly SetNewAuditVersionService $setNewAuditVersionService,
        private readonly AuditRecordValidator $auditRecordValidator,
        private readonly ChangeSetToAuditFieldsConverterInterface $changeSetToAuditFieldsConverter
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convert(
        array $entityChanges,
        string $transactionId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        EntityReference $impersonation,
        ?string $ownerDescription = null,
        ?string $auditDefaultAction = null
    ): void {
        $needFlush = false;
        $auditEntryClass = $this->auditEntityMapper->getAuditEntryClass($this->getEntityByReference($user));
        $auditFieldClass = $this->auditEntityMapper->getAuditEntryFieldClassForAuditEntry($auditEntryClass);
        /** @var EntityManagerInterface $auditEntityManager */
        $auditEntityManager = $this->doctrine->getManagerForClass($auditEntryClass);
        foreach ($entityChanges as $entityChange) {
            if (!$this->auditRecordValidator->validateAuditRecord($entityChange, $auditDefaultAction)) {
                continue;
            }

            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            if (!$this->configProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $entityMetadata = $this->getEntityMetadata($entityClass);
            $fields = $this->changeSetToAuditFieldsConverter->convert(
                $auditEntryClass,
                $auditFieldClass,
                $entityMetadata,
                $entityChange['change_set'] ?? []
            );

            if (empty($fields)) {
                continue;
            }

            $auditEntry = $this->findOrCreateAuditEntry(
                $auditEntityManager,
                $auditEntryClass,
                $transactionId,
                $entityClass,
                $entityId,
                $loggedAt,
                $user,
                $organization,
                $impersonation
            );

            foreach ($fields as $field) {
                $existingField = $auditEntry->getField($field->getField());
                if ($existingField && $entityMetadata->isCollectionValuedAssociation($existingField->getField())) {
                    $existingField->mergeCollectionField($field);
                } else {
                    $auditEntry->addField($field);
                }
                $needFlush = true;
            }

            if (!$auditEntry->getAction()) {
                $auditEntry->setAction($this->guessAuditAction($auditEntry, $auditDefaultAction));
                $needFlush = true;
            }

            if ($ownerDescription) {
                $auditEntry->setOwnerDescription($ownerDescription);
                $needFlush = true;
            }

            if (!empty($entityChange['additional_fields'])) {
                $auditEntry->setAdditionalFields($entityChange['additional_fields']);
                $needFlush = true;
            }
        }

        if ($needFlush) {
            $auditEntityManager->flush();
            $this->entityMetadataCache = [];
        }
    }

    private function guessAuditAction(AbstractAudit $audit, ?string $defaultAction): string
    {
        if ($audit->getAction()) {
            return $audit->getAction();
        }

        if ($audit->getVersion() < 2) {
            return $defaultAction ?: AbstractAudit::ACTION_CREATE;
        }

        return $defaultAction ?: AbstractAudit::ACTION_UPDATE;
    }

    private function getEntityByReference(EntityReference $reference): ?object
    {
        if (!$reference->isLoaded()) {
            $reference->setEntity(
                $this->doctrine->getRepository($reference->getClassName())->find($reference->getId())
            );
        }

        return $reference->getEntity();
    }

    private function findOrCreateAuditEntry(
        EntityManagerInterface $auditEntityManager,
        string $auditEntryClass,
        string $transactionId,
        string $entityClass,
        mixed $entityId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        EntityReference $impersonation
    ): AbstractAudit {
        $auditEntry = $this->getAuditRepository($auditEntryClass)
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.transactionId = :transactionId AND a.objectClass = :objectClass AND a.objectId = :objectId')
            ->setParameter('transactionId', $transactionId)
            ->setParameter('objectClass', $entityClass)
            ->setParameter('objectId', (string)$entityId)
            ->orderBy('a.version', Criteria::DESC)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $auditEntry) {
            try {
                /** @var AbstractAudit $auditEntry */
                $auditEntry = $auditEntityManager->getClassMetadata($auditEntryClass)->newInstance();
                $auditEntry->setTransactionId($transactionId);
                $auditEntry->setObjectClass($entityClass);
                $auditEntry->setObjectId((string)$entityId);
                $auditEntry->setLoggedAt($loggedAt);
                $auditEntry->setUser($this->getEntityByReference($user));
                $auditEntry->setOrganization($this->getEntityByReference($organization));
                $auditEntry->setImpersonation($this->getEntityByReference($impersonation));
                $auditEntry->setObjectName(
                    $this->entityNameProvider->getEntityName($auditEntryClass, $entityClass, $entityId)
                );

                $auditEntityManager->persist($auditEntry);
                $auditEntityManager->flush($auditEntry);
            } catch (\Throwable $e) {
                if ($this->isRetryableException($e)) {
                    // We should stop the process when the same audit entry appears in DB during the save.
                    throw new WrongDataAuditEntryStateException($auditEntry);
                }
                throw $e;
            }

            $this->setNewAuditVersionService->setVersion($auditEntry);
        }

        return $auditEntry;
    }

    private function getAuditRepository(string $auditEntryClass): EntityRepository
    {
        return $this->doctrine->getRepository($auditEntryClass);
    }

    private function getEntityMetadata(string $entityClass): ClassMetadata
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($entityClass);

        if (!isset($this->entityMetadataCache[$entityClass])) {
            $this->entityMetadataCache[$entityClass] = $entityManager->getClassMetadata($entityClass);
        }

        return $this->entityMetadataCache[$entityClass];
    }

    private function isRetryableException(\Throwable $exception): bool
    {
        return $exception instanceof RetryableException
            || $exception instanceof UniqueConstraintViolationException
            || $exception instanceof ForeignKeyConstraintViolationException;
    }
}
