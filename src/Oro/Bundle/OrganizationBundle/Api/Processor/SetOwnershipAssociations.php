<?php

namespace Oro\Bundle\OrganizationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Sets an entity ownership associations based on the current security context.
 */
class SetOwnershipAssociations implements ProcessorInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /**
     * @param PropertyAccessorInterface          $propertyAccessor
     * @param TokenAccessorInterface             $tokenAccessor
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     */
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $ownershipMetadata = $this->ownershipMetadataProvider->getMetadata($context->getClassName());
        if ($ownershipMetadata->hasOwner()) {
            $entity = $context->getData();
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

    /**
     * @param User         $user
     * @param Organization $organization
     *
     * @return BusinessUnit|null
     */
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
