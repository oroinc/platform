<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Helper;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class DefaultOwnerHelper
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /**
     * @param RegistryInterface         $registry
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     */
    public function __construct(RegistryInterface $registry, OwnershipMetadataProvider $ownershipMetadataProvider)
    {
        $this->registry                  = $registry;
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
     * @return OwnershipMetadata
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
