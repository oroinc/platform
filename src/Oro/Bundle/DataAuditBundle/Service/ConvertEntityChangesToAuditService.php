<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;

class ConvertEntityChangesToAuditService
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var SetNewAuditVersionService
     */
    private $setNewAuditVersionService;

    /**
     * @var FindOrCreateAuditService
     */
    private $findOrCreateAuditService;

    /**
     * @var AuditConfigProvider
     */
    private $configProvider;

    /**
     * @var EntityNameProvider
     */
    private $entityNameProvider;

    /**
     * @var ConvertChangeSetToAuditFieldsService
     */
    private $convertChangeSetToAuditFieldsService;

    /**
     * @param ManagerRegistry                      $doctrine
     * @param SetNewAuditVersionService            $setNewAuditVersionService
     * @param FindOrCreateAuditService             $findOrCreateAuditService
     * @param AuditConfigProvider                  $configProvider
     * @param EntityNameProvider                   $entityNameProvider
     * @param ConvertChangeSetToAuditFieldsService $convertChangeSetToAuditFieldsService
     */
    public function __construct(
        ManagerRegistry $doctrine,
        SetNewAuditVersionService $setNewAuditVersionService,
        FindOrCreateAuditService $findOrCreateAuditService,
        AuditConfigProvider $configProvider,
        EntityNameProvider $entityNameProvider,
        ConvertChangeSetToAuditFieldsService $convertChangeSetToAuditFieldsService
    ) {
        $this->doctrine = $doctrine;
        $this->setNewAuditVersionService = $setNewAuditVersionService;
        $this->findOrCreateAuditService = $findOrCreateAuditService;
        $this->configProvider = $configProvider;
        $this->entityNameProvider = $entityNameProvider;
        $this->convertChangeSetToAuditFieldsService = $convertChangeSetToAuditFieldsService;
    }

    /**
     * @param array           $entitiesChanges
     * @param string          $transactionId
     * @param \DateTime       $loggedAt
     * @param EntityReference $user
     * @param EntityReference $organization
     * @param string|null     $auditDefaultAction
     */
    public function convert(
        array $entitiesChanges,
        $transactionId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        $auditDefaultAction = null
    ) {
        $auditEntityManager = $this->doctrine->getManagerForClass(Audit::class);

        foreach ($entitiesChanges as $entityChange) {
            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->doctrine->getManagerForClass($entityClass);
            $entityMetadata = $entityManager->getClassMetadata($entityClass);
            if (!$this->configProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $fields = $this->convertChangeSetToAuditFieldsService->convert(
                $entityMetadata,
                $entityChange['change_set']
            );
            if (empty($fields)) {
                continue;
            }

            $audit = $this->findOrCreateAuditEntity($transactionId, $user, $entityClass, $entityId);
            if (!$audit->getVersion()) {
                $audit->setUser($this->getEntityByReference($user));
                $audit->setOrganization($this->getEntityByReference($organization));
                $audit->setLoggedAt($loggedAt);
                $audit->setObjectName($this->entityNameProvider->getEntityName($entityClass, $entityId));
                $auditEntityManager->flush();

                $this->setNewAuditVersionService->setVersion($audit);
            }

            if (!$audit->getAction()) {
                $audit->setAction($this->guessAuditAction($audit, $auditDefaultAction));
                $auditEntityManager->flush();
            }

            foreach ($fields as $newField) {
                $currentField = $audit->getField($newField->getField());
                if ($currentField && $entityMetadata->isCollectionValuedAssociation($currentField->getField())) {
                    $currentField->mergeCollectionField($newField);
                } else {
                    $audit->addField($newField);
                }
            }

            $auditEntityManager->persist($audit);
        }

        $auditEntityManager->flush();
    }

    /**
     * @param array             $entitiesChanges
     * @param string            $transactionId
     * @param \DateTime         $loggedAt
     * @param EntityReference   $user
     * @param EntityReference   $organization
     * @param string            $auditDefaultAction
     */
    public function convertSkipFields(
        array $entitiesChanges,
        $transactionId,
        \DateTime $loggedAt,
        EntityReference $user,
        EntityReference $organization,
        $auditDefaultAction
    ) {
        $auditEntityManager = $this->doctrine->getManagerForClass(Audit::class);

        foreach ($entitiesChanges as $entityChange) {
            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            if (!$this->configProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $audit = $this->findOrCreateAuditEntity($transactionId, $user, $entityClass, $entityId);
            if (!$audit->getAction()) {
                // a new audit entity
                $audit->setAction($auditDefaultAction);
                $audit->setUser($this->getEntityByReference($user));
                $audit->setOrganization($this->getEntityByReference($organization));
                $audit->setLoggedAt($loggedAt);
                $audit->setObjectName($this->entityNameProvider->getEntityName($entityClass, $entityId));
                $auditEntityManager->flush();

                $this->setNewAuditVersionService->setVersion($audit);
            }

            $auditEntityManager->persist($audit);
        }

        $auditEntityManager->flush();
    }

    /**
     * @param AbstractAudit $audit
     * @param $defaultAction
     *
     * @return string
     */
    protected function guessAuditAction(AbstractAudit $audit, $defaultAction)
    {
        if ($audit->getAction()) {
            return $audit->getAction();
        }

        if ($audit->getVersion() < 2) {
            return $defaultAction ?: Audit::ACTION_CREATE;
        }

        return $defaultAction ?: Audit::ACTION_UPDATE;
    }

    /**
     * @param string          $transactionId
     * @param EntityReference $user
     * @param string          $entityClass
     * @param string          $entityId
     *
     * @return AbstractAudit
     */
    private function findOrCreateAuditEntity(
        $transactionId,
        EntityReference $user,
        $entityClass,
        $entityId
    ) {
        return $this->findOrCreateAuditService->findOrCreate(
            $this->getEntityByReference($user),
            $entityClass,
            $entityId,
            $transactionId
        );
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
}
