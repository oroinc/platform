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
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * This converter is intended to add the data audit records to the database
 * based on a list of entity changes.
 * @see \Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener
 */
class EntityChangesToAuditEntryConverter
{
    private ManagerRegistry $doctrine;
    private AuditEntityMapper $auditEntityMapper;
    private AuditConfigProvider $configProvider;
    private EntityNameProvider $entityNameProvider;
    private SetNewAuditVersionService $setNewAuditVersionService;
    private AuditRecordValidator $auditRecordValidator;
    private ChangeSetToAuditFieldsConverterInterface $changeSetToAuditFieldsConverter;

    /**
     * Local cache of entities metadata.
     * To avoid performance impact of getting entities metadata in "convert" method.
     */
    private array $entityMetadataCache = [];

    public function __construct(
        ManagerRegistry $doctrine,
        AuditEntityMapper $auditEntityMapper,
        AuditConfigProvider $configProvider,
        EntityNameProvider $entityNameProvider,
        SetNewAuditVersionService $setNewAuditVersionService,
        AuditRecordValidator $auditRecordValidator,
        ChangeSetToAuditFieldsConverterInterface $changeSetToAuditFieldsConverter
    ) {
        $this->doctrine = $doctrine;
        $this->auditEntityMapper = $auditEntityMapper;
        $this->configProvider = $configProvider;
        $this->entityNameProvider = $entityNameProvider;
        $this->setNewAuditVersionService = $setNewAuditVersionService;
        $this->auditRecordValidator = $auditRecordValidator;
        $this->changeSetToAuditFieldsConverter = $changeSetToAuditFieldsConverter;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param array           $entityChanges
     * @param string          $transactionId
     * @param \DateTime       $loggedAt
     * @param EntityReference $user
     * @param EntityReference $organization
     * @param EntityReference $impersonation
     * @param string|null     $ownerDescription
     * @param string|null     $auditDefaultAction
     */
    public function convert(
        array $entityChanges,
        $transactionId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        EntityReference $impersonation,
        $ownerDescription = null,
        $auditDefaultAction = null
    ) {
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
                $impersonation,
                null
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

    /**
     * @param array           $entityChanges
     * @param string          $transactionId
     * @param \DateTime       $loggedAt
     * @param EntityReference $user
     * @param EntityReference $organization
     * @param EntityReference $impersonation
     * @param string          $ownerDescription
     * @param string          $auditDefaultAction
     */
    public function convertSkipFields(
        array $entityChanges,
        $transactionId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        EntityReference $impersonation,
        $ownerDescription = null,
        $auditDefaultAction = null
    ) {
        $auditEntryClass = $this->auditEntityMapper->getAuditEntryClass($this->getEntityByReference($user));
        /** @var EntityManagerInterface $auditEntityManager */
        $auditEntityManager = $this->doctrine->getManagerForClass($auditEntryClass);

        foreach ($entityChanges as $entityChange) {
            if (!$this->auditRecordValidator->validateAuditRecord($entityChange, $auditDefaultAction)) {
                continue;
            }

            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            if (\is_a($entityClass, AbstractEnumValue::class, true) ||
                !$this->configProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $audit = $this->findOrCreateAuditEntry(
                $auditEntityManager,
                $auditEntryClass,
                $transactionId,
                $entityClass,
                $entityId,
                $loggedAt,
                $user,
                $organization,
                $impersonation,
                $auditDefaultAction
            );

            if (!empty($entityChange['additional_fields'])) {
                $audit->setAdditionalFields($entityChange['additional_fields']);
            }

            if ($ownerDescription) {
                $audit->setOwnerDescription($ownerDescription);
            }
        }

        $auditEntityManager->flush();
    }

    /**
     * @param AbstractAudit $audit
     * @param string|null   $defaultAction
     *
     * @return string
     */
    private function guessAuditAction(AbstractAudit $audit, $defaultAction)
    {
        if ($audit->getAction()) {
            return $audit->getAction();
        }

        if ($audit->getVersion() < 2) {
            return $defaultAction ? : AbstractAudit::ACTION_CREATE;
        }

        return $defaultAction ? : AbstractAudit::ACTION_UPDATE;
    }

    /**
     * @param EntityReference $reference
     *
     * @return object|null
     */
    private function getEntityByReference(EntityReference $reference)
    {
        if (!$reference->isLoaded()) {
            $reference->setEntity(
                $this->doctrine->getRepository($reference->getClassName())->find($reference->getId())
            );
        }

        return $reference->getEntity();
    }

    /**
     * @param EntityManagerInterface $auditEntityManager
     * @param string                 $auditEntryClass
     * @param string                 $transactionId
     * @param string                 $entityClass
     * @param string                 $entityId
     * @param \DateTime              $loggedAt
     * @param EntityReference        $user
     * @param EntityReference        $organization
     * @param EntityReference        $impersonation
     * @param string|null            $action
     *
     * @return AbstractAudit
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function findOrCreateAuditEntry(
        EntityManagerInterface $auditEntityManager,
        $auditEntryClass,
        $transactionId,
        $entityClass,
        $entityId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        EntityReference $impersonation,
        $action = null
    ) {
        $auditEntry = $this->getAuditRepository($auditEntryClass)
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.transactionId = :transactionId AND a.objectClass = :objectClass AND a.objectId = :objectId')
            ->setParameter('transactionId', $transactionId)
            ->setParameter('objectClass', $entityClass)
            ->setParameter('objectId', (string) $entityId)
            ->orderBy('a.version', Criteria::DESC)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $auditEntry) {
            try {
                /** @var AbstractAudit $auditEntry */
                $auditEntry = $auditEntityManager->getClassMetadata($auditEntryClass)->newInstance();
                $auditEntry->setTransactionId($transactionId);
                $auditEntry->setObjectClass($entityClass);
                $auditEntry->setObjectId((string) $entityId);
                $auditEntry->setLoggedAt($loggedAt);
                $auditEntry->setUser($this->getEntityByReference($user));
                $auditEntry->setOrganization($this->getEntityByReference($organization));
                $auditEntry->setImpersonation($this->getEntityByReference($impersonation));
                $auditEntry->setObjectName(
                    $this->entityNameProvider->getEntityName($auditEntryClass, $entityClass, $entityId)
                );
                $auditEntry->setAction($action);

                $auditEntityManager->persist($auditEntry);
                $auditEntityManager->flush($auditEntry);
            } catch (\Throwable $e) {
                if ($this->isRetryableException($e)) {
                    // We should stop the process when the same audit entry appears in DB during the save.
                    throw new WrongDataAuditEntryStateException($auditEntry);
                } else {
                    throw $e;
                }
            }

            $this->setNewAuditVersionService->setVersion($auditEntry);
        }

        return $auditEntry;
    }

    /**
     * @param string $auditEntryClass
     *
     * @return EntityRepository
     */
    private function getAuditRepository($auditEntryClass)
    {
        return $this->doctrine->getRepository($auditEntryClass);
    }

    /**
     * @param string $entityClass
     *
     * @return ClassMetadata
     */
    private function getEntityMetadata($entityClass)
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
