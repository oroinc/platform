<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

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
     * @var GetEntityAuditMetadataService
     */
    private $getEntityAuditMetadataService;

    /**
     * @var GetHumanReadableEntityNameService
     */
    private $getHumanReadableEntityNameService;

    /**
     * @var ConvertChangeSetToAuditFieldsService
     */
    private $convertChangeSetToAuditFieldsService;

    /**
     * @param ManagerRegistry $doctrine
     * @param SetNewAuditVersionService $setNewAuditVersionService
     * @param FindOrCreateAuditService $findOrCreateAuditService
     * @param GetEntityAuditMetadataService $getEntityAuditMetadataService
     * @param GetHumanReadableEntityNameService $getHumanReadableEntityNameService
     * @param ConvertChangeSetToAuditFieldsService $convertChangeSetToAuditFieldsService
     */
    public function __construct(
        ManagerRegistry $doctrine,
        SetNewAuditVersionService $setNewAuditVersionService,
        FindOrCreateAuditService $findOrCreateAuditService,
        GetEntityAuditMetadataService $getEntityAuditMetadataService,
        GetHumanReadableEntityNameService $getHumanReadableEntityNameService,
        ConvertChangeSetToAuditFieldsService $convertChangeSetToAuditFieldsService
    ) {
        $this->doctrine = $doctrine;
        $this->setNewAuditVersionService = $setNewAuditVersionService;
        $this->findOrCreateAuditService = $findOrCreateAuditService;
        $this->getEntityAuditMetadataService = $getEntityAuditMetadataService;
        $this->getHumanReadableEntityNameService = $getHumanReadableEntityNameService;
        $this->convertChangeSetToAuditFieldsService = $convertChangeSetToAuditFieldsService;
    }

    /**
     * @param array $entitiesChanges
     * @param $transactionId
     * @param \DateTime $loggedAt
     * @param $auditDefaultAction
     * @param AbstractUser|null $user
     * @param Organization|null $organization
     */
    public function convert(
        array $entitiesChanges,
        $transactionId,
        \DateTime $loggedAt,
        $auditDefaultAction = null,
        AbstractUser $user = null,
        Organization $organization = null
    ) {
        $auditEntityManager = $this->doctrine->getManagerForClass(Audit::class);
        
        foreach ($entitiesChanges as $entityChange) {
            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->doctrine->getManagerForClass($entityClass);
            $entityMeta = $entityManager->getClassMetadata($entityClass);
            $entityAuditMeta = $this->getEntityAuditMetadataService->getMetadata($entityClass);
            if (false == $entityAuditMeta) {
                continue;
            }

            $fields = $this->convertChangeSetToAuditFieldsService->convert(
                $entityAuditMeta,
                $entityMeta,
                $entityChange['change_set']
            );

            $audit = $this->findOrCreateAuditService->findOrCreate($user, $entityId, $entityClass, $transactionId);
            if (false == $audit->getVersion()) {
                $audit->setUser($user);
                $audit->setOrganization($organization);
                $audit->setLoggedAt($loggedAt);
                $audit->setObjectName($this->getHumanReadableEntityNameService->getName($entityClass, $entityId));
                $auditEntityManager->flush();

                $this->setNewAuditVersionService->setVersion($audit);
            }

            if (false == $audit->getAction()) {
                $audit->setAction($this->guessAuditAction($audit, $auditDefaultAction));
                $auditEntityManager->flush();
            }

            foreach ($fields as $newField) {
                $currentField = $audit->getField($newField->getField());
                if ($currentField && $entityMeta->isCollectionValuedAssociation($currentField->getField())) {
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
     * @param array $entitiesChanges
     * @param $transactionId
     * @param $auditDefaultAction
     * @param \DateTime $loggedAt
     * @param AbstractUser|null $user
     * @param Organization|null $organization
     */
    public function convertSkipFields(
        array $entitiesChanges,
        $transactionId,
        $auditDefaultAction,
        \DateTime $loggedAt,
        AbstractUser $user = null,
        Organization $organization = null
    ) {
        $auditEntityManager = $this->doctrine->getManagerForClass(Audit::class);

        foreach ($entitiesChanges as $entityChange) {
            $entityClass = $entityChange['entity_class'];
            $entityId = $entityChange['entity_id'];
            /** @var EntityManagerInterface $entityManager */
            $entityAuditMeta = $this->getEntityAuditMetadataService->getMetadata($entityClass);
            if (false == $entityAuditMeta) {
                continue;
            }

            $audit = $this->findOrCreateAuditService->findOrCreate($user, $entityId, $entityClass, $transactionId);
            if (false == $audit->getAction()) {
                // a new audit entity
                $audit->setAction($auditDefaultAction);
                $audit->setUser($user);
                $audit->setOrganization($organization);
                $audit->setLoggedAt($loggedAt);
                $audit->setObjectName($this->getHumanReadableEntityNameService->getName($entityClass, $entityId));
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
}
