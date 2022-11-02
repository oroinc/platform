<?php

namespace Oro\Bundle\OrganizationBundle\Ownership;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * This manager helps to check if an owner entity has associations to other entities.
 */
class OwnerDeletionManager
{
    /** @var ContainerInterface */
    private $checkerContainer;

    /** @var ConfigProvider */
    private $ownershipProvider;

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadata;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ObjectIdAccessor */
    private $objectIdAccessor;

    public function __construct(
        ContainerInterface $checkerContainer,
        ConfigProvider $ownershipProvider,
        OwnershipMetadataProviderInterface $ownershipMetadata,
        DoctrineHelper $doctrineHelper,
        ObjectIdAccessor $objectIdAccessor
    ) {
        $this->checkerContainer = $checkerContainer;
        $this->ownershipProvider = $ownershipProvider;
        $this->ownershipMetadata = $ownershipMetadata;
        $this->doctrineHelper = $doctrineHelper;
        $this->objectIdAccessor = $objectIdAccessor;
    }

    /**
     * Checks whether the given entity is an owner.
     *
     * @param object $entity
     *
     * @return bool true
     */
    public function isOwner($entity): bool
    {
        return $this->getOwnerType($entity) !== OwnershipType::OWNER_TYPE_NONE;
    }

    /**
     * Checks if the given owner owns at least one entity.
     *
     * @param object $owner
     *
     * @return bool
     */
    public function hasAssignments($owner): bool
    {
        if ($owner instanceof Organization) {
            return $this->hasOrganizationAssignments($owner);
        }

        $ownerType = $this->getOwnerType($owner);
        if (OwnershipType::OWNER_TYPE_NONE === $ownerType) {
            return false;
        }

        $ownerId = $this->objectIdAccessor->getId($owner);
        $configs = $this->ownershipProvider->getConfigs(null, true);
        foreach ($configs as $config) {
            if ($config->get('owner_type') === $ownerType) {
                $entityClass = $config->getId()->getClassName();
                $hasAssignments = $this->getAssignmentChecker($entityClass)->hasAssignments(
                    $ownerId,
                    $entityClass,
                    $this->ownershipMetadata->getMetadata($entityClass)->getOwnerFieldName(),
                    $this->doctrineHelper->getEntityManagerForClass($entityClass)
                );
                if ($hasAssignments) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if there is at least one entity in the given organization.
     */
    private function hasOrganizationAssignments(Organization $organization): bool
    {
        $organizationId = $this->objectIdAccessor->getId($organization);
        $configs = $this->ownershipProvider->getConfigs(null, true);
        foreach ($configs as $config) {
            $ownerType = $config->get('owner_type');
            if ($ownerType) {
                $entityClass = $config->getId()->getClassName();
                $metadata = $this->ownershipMetadata->getMetadata($entityClass);
                $em = $this->doctrineHelper->getEntityManagerForClass($entityClass);
                if (OwnershipType::OWNER_TYPE_ORGANIZATION === $ownerType) {
                    $hasAssignments = $this->hasAssignmentsForOrganization(
                        $organizationId,
                        $entityClass,
                        $metadata->getOwnerFieldName(),
                        $em
                    );
                } else {
                    $hasAssignments = $this->getAssignmentChecker($entityClass)->hasAssignments(
                        $organizationId,
                        $entityClass,
                        $metadata->getOrganizationFieldName(),
                        $em
                    );
                }
                if ($hasAssignments) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasAssignmentsForOrganization(
        int $organizationId,
        string $entityClass,
        string $organizationFieldName,
        EntityManagerInterface $em
    ): bool {
        $findResult = $em->createQueryBuilder()
            ->from($entityClass, 'entity')
            ->select('organization.id')
            ->innerJoin(sprintf('entity.%s', $organizationFieldName), 'organization')
            ->where('organization.id = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return !empty($findResult);
    }

    private function getAssignmentChecker(string $entityClass): OwnerAssignmentCheckerInterface
    {
        if ($this->checkerContainer->has($entityClass)) {
            return $this->checkerContainer->get($entityClass);
        }

        return $this->checkerContainer->get('default');
    }

    /**
     * @param object $owner
     *
     * @return string
     */
    private function getOwnerType($owner): string
    {
        if (is_a($owner, $this->ownershipMetadata->getUserClass())) {
            return OwnershipType::OWNER_TYPE_USER;
        }

        if (is_a($owner, $this->ownershipMetadata->getBusinessUnitClass())) {
            return OwnershipType::OWNER_TYPE_BUSINESS_UNIT;
        }

        if (is_a($owner, $this->ownershipMetadata->getOrganizationClass())) {
            return OwnershipType::OWNER_TYPE_ORGANIZATION;
        }

        return OwnershipType::OWNER_TYPE_NONE;
    }
}
