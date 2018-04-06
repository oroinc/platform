<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Helper;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DefaultOwnerHelper
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var OwnershipMetadataProviderInterface */
    protected $ownershipMetadataProvider;

    /**
     * @param RegistryInterface                  $registry
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     */
    public function __construct(
        RegistryInterface $registry,
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
