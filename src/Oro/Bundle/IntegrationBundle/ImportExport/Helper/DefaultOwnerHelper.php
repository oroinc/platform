<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Helper;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Populates ownership information on imported entities based on integration channel configuration.
 *
 * This helper class is used during import/export operations to automatically assign the default
 * owner (user or organization) from the integration channel to imported entities. It respects
 * the entity's ownership metadata to determine which ownership fields should be populated,
 * ensuring that imported data maintains proper ownership relationships and security constraints.
 */
class DefaultOwnerHelper
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    public function __construct(
        ManagerRegistry $registry,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->registry = $registry;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * Populate owner to target entity based on integration configuration and entity's ownership type
     *
     * @param object      $entity
     * @param Integration $integration
     */
    public function populateChannelOwner($entity, Integration $integration)
    {
        $defaultUserOwner = $integration->getDefaultUserOwner();

        $className         = ClassUtils::getClass($entity);
        $doctrineMetadata  = $this->getEm()->getClassMetadata($className);
        $ownershipMetadata = $this->getMetadata($className);

        if ($defaultUserOwner && $ownershipMetadata->isUserOwned()) {
            $defaultUserOwner = $this->ensureNotDetached($defaultUserOwner);
            $doctrineMetadata->setFieldValue($entity, $ownershipMetadata->getOwnerFieldName(), $defaultUserOwner);
        }
        $defaultOrganization = $integration->getOrganization();
        if ($defaultOrganization && $ownershipMetadata->getOrganizationFieldName()) {
            $defaultOrganization = $this->ensureNotDetached($defaultOrganization);
            $doctrineMetadata->setFieldValue(
                $entity,
                $ownershipMetadata->getOrganizationFieldName(),
                $defaultOrganization
            );
        }
    }

    /**
     * Get metadata for entity
     *
     * @param string $entityFQCN
     *
     * @return OwnershipMetadataInterface
     */
    protected function getMetadata($entityFQCN)
    {
        return $this->ownershipMetadataProvider->getMetadata($entityFQCN);
    }

    /**
     * @param object $entity
     *
     * @return object
     */
    protected function ensureNotDetached($entity)
    {
        $uow = $this->getEm()->getUnitOfWork();

        if ($uow->getEntityState($entity, UnitOfWork::STATE_DETACHED) === UnitOfWork::STATE_DETACHED) {
            $entity = $this->getEm()->find(ClassUtils::getClass($entity), $entity->getId());
            $uow->markReadOnly($entity);
        }

        return $entity;
    }

    /**
     * @return EntityManager
     */
    private function getEm()
    {
        return $this->registry->getManager();
    }
}
