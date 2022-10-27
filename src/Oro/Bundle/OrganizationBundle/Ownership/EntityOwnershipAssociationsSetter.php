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
 * based on the current security context when these associations were not set yet.
 */
class EntityOwnershipAssociationsSetter
{
    private PropertyAccessorInterface $propertyAccessor;
    private TokenAccessorInterface $tokenAccessor;
    private OwnershipMetadataProviderInterface $ownershipMetadataProvider;

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
     * Sets an owner and an organization for the given entity based on the current security context
     * when these associations were not set yet.
     *
     * @result string[] The names of fields that value was changed
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setOwnershipAssociations(object $entity): array
    {
        if (!$this->tokenAccessor->hasUser()) {
            return [];
        }

        $result = [];
        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata(ClassUtils::getClass($entity));
        if ($ownershipMetadata->hasOwner()) {
            if ($ownershipMetadata->isUserOwned()) {
                $ownerFieldName = $ownershipMetadata->getOwnerFieldName();
                if ($this->setUser($entity, $ownerFieldName)) {
                    $result[] = $ownerFieldName;
                }
                $organizationFieldName = $ownershipMetadata->getOrganizationFieldName();
                if ($this->setOrganization($entity, $organizationFieldName)) {
                    $result[] = $organizationFieldName;
                }
            } elseif ($ownershipMetadata->isBusinessUnitOwned()) {
                $ownerFieldName = $ownershipMetadata->getOwnerFieldName();
                if ($this->setBusinessUnit($entity, $ownerFieldName)) {
                    $result[] = $ownerFieldName;
                }
                $organizationFieldName = $ownershipMetadata->getOrganizationFieldName();
                if ($this->setOrganization($entity, $organizationFieldName)) {
                    $result[] = $organizationFieldName;
                }
            } elseif ($ownershipMetadata->isOrganizationOwned()) {
                $ownerFieldName = $ownershipMetadata->getOwnerFieldName();
                if ($this->setOrganization($entity, $ownerFieldName)) {
                    $result[] = $ownerFieldName;
                }
            }
        }

        return $result;
    }

    private function setUser(object $entity, ?string $userFieldName): bool
    {
        if (!$userFieldName) {
            return false;
        }

        $result = false;
        $entityUser = $this->propertyAccessor->getValue($entity, $userFieldName);
        if (null === $entityUser) {
            $user = $this->getUser();
            if (null !== $user) {
                $this->propertyAccessor->setValue($entity, $userFieldName, $user);
                $result = true;
            }
        }

        return $result;
    }

    private function setBusinessUnit(object $entity, ?string $businessUnitFieldName): bool
    {
        if (!$businessUnitFieldName) {
            return false;
        }

        if ($entity instanceof BusinessUnit) {
            return false;
        }

        $result = false;
        $entityBusinessUnit = $this->propertyAccessor->getValue($entity, $businessUnitFieldName);
        if (null === $entityBusinessUnit) {
            $user = $this->getUser();
            if (null !== $user) {
                $organization = $this->tokenAccessor->getOrganization();
                if (null !== $organization) {
                    $businessUnit = $this->getBusinessUnit($user, $organization);
                    if (null !== $businessUnit) {
                        $this->propertyAccessor->setValue($entity, $businessUnitFieldName, $businessUnit);
                        $result = true;
                    }
                }
            }
        }

        return $result;
    }

    private function setOrganization(object $entity, ?string $organizationFieldName): bool
    {
        if (!$organizationFieldName) {
            return false;
        }

        $result = false;
        $entityOrganization = $this->propertyAccessor->getValue($entity, $organizationFieldName);
        if (null === $entityOrganization) {
            $organization = $this->tokenAccessor->getOrganization();
            if (null !== $organization) {
                $this->propertyAccessor->setValue($entity, $organizationFieldName, $organization);
                $result = true;
            }
        }

        return $result;
    }

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
