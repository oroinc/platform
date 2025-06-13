<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

/**
 * Loads an entity during complex data import.
 */
class ComplexDataConvertationEntityLoader implements ComplexDataConvertationEntityLoaderInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly TokenAccessorInterface $tokenAccessor,
        private readonly OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
    }

    #[\Override]
    public function loadEntity(string $entityClass, array $criteria): ?object
    {
        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($entityClass);
        if ($ownershipMetadata->hasOwner()) {
            $organizationId = $this->tokenAccessor->getOrganizationId();
            if (null === $organizationId) {
                return null;
            }
            $criteria[$ownershipMetadata->getOrganizationFieldName()] = $organizationId;
        }

        return $this->doctrine->getRepository($entityClass)->findOneBy($criteria);
    }
}
