<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;

class ConvertEntityChangesToAuditService
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var AuditEntityMapper */
    private $auditEntityMapper;

    /** @var AuditConfigProvider */
    private $configProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    /** @var SetNewAuditVersionService */
    private $setNewAuditVersionService;

    /** @var ConvertChangeSetToAuditFieldsService */
    private $changeSetToAuditFieldsConverter;

    /**
     * @param ManagerRegistry                      $doctrine
     * @param AuditEntityMapper                    $auditEntityMapper
     * @param AuditConfigProvider                  $configProvider
     * @param EntityNameProvider                   $entityNameProvider
     * @param SetNewAuditVersionService            $setNewAuditVersionService
     * @param ConvertChangeSetToAuditFieldsService $changeSetToAuditFieldsConverter
     */
    public function __construct(
        ManagerRegistry $doctrine,
        AuditEntityMapper $auditEntityMapper,
        AuditConfigProvider $configProvider,
        EntityNameProvider $entityNameProvider,
        SetNewAuditVersionService $setNewAuditVersionService,
        ConvertChangeSetToAuditFieldsService $changeSetToAuditFieldsConverter
    ) {
        $this->doctrine = $doctrine;
        $this->auditEntityMapper = $auditEntityMapper;
        $this->configProvider = $configProvider;
        $this->entityNameProvider = $entityNameProvider;
        $this->setNewAuditVersionService = $setNewAuditVersionService;
        $this->changeSetToAuditFieldsConverter = $changeSetToAuditFieldsConverter;
    }

    /**
     * @param array           $entityChanges
     * @param string          $transactionId
     * @param \DateTime       $loggedAt
     * @param EntityReference $user
     * @param EntityReference $organization
     * @param string|null     $auditDefaultAction
     */
    public function convert(
        array $entityChanges,
        $transactionId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        $auditDefaultAction = null
    ) {
        $auditEntryClass = $this->auditEntityMapper->getAuditEntryClass($this->getEntityByReference($user));
        $auditEntityManager = $this->doctrine->getManagerForClass($auditEntryClass);

        foreach ($entityChanges as $entityChange) {
            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->doctrine->getManagerForClass($entityClass);
            $entityMetadata = $entityManager->getClassMetadata($entityClass);
            if (!$this->configProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $fields = $this->changeSetToAuditFieldsConverter->convert(
                $auditEntryClass,
                $entityMetadata,
                $entityChange['change_set']
            );
            if (empty($fields)) {
                continue;
            }

            $audit = $this->findAuditEntity($auditEntryClass, $transactionId, $entityClass, $entityId);
            if (null === $audit) {
                $audit = $this->createAuditEntity(
                    $auditEntityManager,
                    $auditEntryClass,
                    $transactionId,
                    $entityClass,
                    $entityId,
                    $loggedAt,
                    $user,
                    $organization
                );
            }
            if (!$audit->getAction()) {
                $audit->setAction($this->guessAuditAction($audit, $auditDefaultAction));
                $auditEntityManager->flush();
            }

            foreach ($fields as $field) {
                $existingField = $audit->getField($field->getField());
                if ($existingField && $entityMetadata->isCollectionValuedAssociation($existingField->getField())) {
                    $existingField->mergeCollectionField($field);
                } else {
                    $audit->addField($field);
                }
            }
        }

        $auditEntityManager->flush();
    }

    /**
     * @param array           $entityChanges
     * @param string          $transactionId
     * @param \DateTime       $loggedAt
     * @param EntityReference $user
     * @param EntityReference $organization
     * @param string          $auditDefaultAction
     */
    public function convertSkipFields(
        array $entityChanges,
        $transactionId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        $auditDefaultAction
    ) {
        $auditEntryClass = $this->auditEntityMapper->getAuditEntryClass($this->getEntityByReference($user));
        $auditEntityManager = $this->doctrine->getManagerForClass($auditEntryClass);

        foreach ($entityChanges as $entityChange) {
            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            if (!$this->configProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $audit = $this->findAuditEntity($auditEntryClass, $transactionId, $entityClass, $entityId);
            if (null === $audit) {
                $this->createAuditEntity(
                    $auditEntityManager,
                    $auditEntryClass,
                    $transactionId,
                    $entityClass,
                    $entityId,
                    $loggedAt,
                    $user,
                    $organization,
                    $auditDefaultAction
                );
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
            return $defaultAction ?: AbstractAudit::ACTION_CREATE;
        }

        return $defaultAction ?: AbstractAudit::ACTION_UPDATE;
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
     * @param string $auditEntryClass
     * @param string $transactionId
     * @param string $entityClass
     * @param string $entityId
     *
     * @return AbstractAudit
     */
    private function findAuditEntity($auditEntryClass, $transactionId, $entityClass, $entityId)
    {
        return $this->getAuditRepository($auditEntryClass)
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.transactionId = :transactionId AND a.objectClass = :objectClass AND a.objectId = :objectId')
            ->setParameter('transactionId', $transactionId)
            ->setParameter('objectClass', $entityClass)
            ->setParameter('objectId', $entityId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param EntityManager   $auditEntityManager
     * @param string          $auditEntryClass
     * @param string          $transactionId
     * @param string          $entityClass
     * @param int             $entityId
     * @param \DateTime       $loggedAt
     * @param EntityReference $user
     * @param EntityReference $organization
     * @param string|null     $action
     *
     * @return AbstractAudit
     */
    private function createAuditEntity(
        EntityManager $auditEntityManager,
        $auditEntryClass,
        $transactionId,
        $entityClass,
        $entityId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        $action = null
    ) {
        /** @var AbstractAudit $audit */
        $audit = $auditEntityManager->getClassMetadata($auditEntryClass)->newInstance();
        $audit->setTransactionId($transactionId);
        $audit->setObjectClass($entityClass);
        $audit->setObjectId($entityId);
        $audit->setLoggedAt($loggedAt);
        $audit->setUser($this->getEntityByReference($user));
        $audit->setOrganization($this->getEntityByReference($organization));
        $audit->setObjectName(
            $this->entityNameProvider->getEntityName($auditEntryClass, $entityClass, $entityId)
        );
        $audit->setAction($action);

        $auditEntityManager->persist($audit);
        $auditEntityManager->flush();

        $this->setNewAuditVersionService->setVersion($audit);

        return $audit;
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
}
