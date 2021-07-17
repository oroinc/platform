<?php

namespace Oro\Bundle\OrganizationBundle\Ownership;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides a functionality to set an owner and an organization for an entity
 * based on the current security context these associations were not set yet.
 */
class EntityOwnershipAssociationsSetter
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        TokenAccessorInterface $tokenAccessor,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->tokenAccessor = $tokenAccessor;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * @param object $entity
     */
    public function setOwnershipAssociations($entity): void
    {
        if (!$this->tokenAccessor->hasUser()) {
            return;
        }

        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata(ClassUtils::getClass($entity));
        if ($ownershipMetadata->hasOwner()) {
            if ($ownershipMetadata->isUserOwned()) {
                $this->setUser($entity, $ownershipMetadata->getOwnerFieldName());
                $this->setOrganization($entity, $ownershipMetadata->getOrganizationFieldName());
            } elseif ($ownershipMetadata->isBusinessUnitOwned()) {
                $this->setBusinessUnit($entity, $ownershipMetadata->getOwnerFieldName());
                $this->setOrganization($entity, $ownershipMetadata->getOrganizationFieldName());
            } elseif ($ownershipMetadata->isOrganizationOwned()) {
                $this->setOrganization($entity, $ownershipMetadata->getOwnerFieldName());
            }
        }
    }

    /**
     * @param object      $entity
     * @param string|null $userFieldName
     */
    private function setUser($entity, ?string $userFieldName): void
    {
        if (!$userFieldName) {
            return;
        }

        $entityUser = $this->propertyAccessor->getValue($entity, $userFieldName);
        if (null === $entityUser) {
            $user = $this->getUser();
            if (null !== $user) {
                $this->propertyAccessor->setValue($entity, $userFieldName, $user);
            }
        }
    }

    /**
     * @param object      $entity
     * @param string|null $businessUnitFieldName
     */
    private function setBusinessUnit($entity, ?string $businessUnitFieldName): void
    {
        if (!$businessUnitFieldName) {
            return;
        }

        if ($entity instanceof BusinessUnit) {
            return;
        }

        $entityBusinessUnit = $this->propertyAccessor->getValue($entity, $businessUnitFieldName);
        if (null === $entityBusinessUnit) {
            $user = $this->getUser();
            if (null !== $user) {
                $organization = $this->tokenAccessor->getOrganization();
                if (null !== $organization) {
                    $businessUnit = $this->getBusinessUnit($user, $organization);
                    if (null !== $businessUnit) {
                        $this->propertyAccessor->setValue($entity, $businessUnitFieldName, $businessUnit);
                    }
                }
            }
        }
    }

    /**
     * @param object      $entity
     * @param string|null $organizationFieldName
     */
    private function setOrganization($entity, ?string $organizationFieldName): void
    {
        if (!$organizationFieldName) {
            return;
        }

        $entityOrganization = $this->propertyAccessor->getValue($entity, $organizationFieldName);
        if (null === $entityOrganization) {
            $organization = $this->tokenAccessor->getOrganization();
            if (null !== $organization) {
                $this->propertyAccessor->setValue($entity, $organizationFieldName, $organization);
            }
        }
    }

    /**
     * @return User
     */
    private function getUser(): ?User
    {
        $user = $this->tokenAccessor->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return $user->getOwner();
    }

    private function getBusinessUnit(User $user, Organization $organization): ?BusinessUnit
    {
        $result = null;
        /** @var BusinessUnit[] $businessUnits */
        $businessUnits = $user->getBusinessUnits();
        foreach ($businessUnits as $businessUnit) {
            $businessUnitOrganization = $businessUnit->getOrganization();
            if (null !== $businessUnitOrganization
                && $businessUnitOrganization->getId() === $organization->getId()
            ) {
                $result = $businessUnit;
                break;
            }
        }

        return $result;
    }
}
