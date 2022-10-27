<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * A service to apply organization related restrictions.
 */
class OrganizationRestrictionProvider implements OrganizationRestrictionProviderInterface
{
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function applyOrganizationRestrictions(
        QueryBuilder $qb,
        ?Organization $organization = null,
        ?string $entityAlias = null
    ): void {
        if (null === $organization) {
            $organization = $this->tokenAccessor->getOrganization();
        }

        if (null !== $organization) {
            $qb
                ->andWhere(($entityAlias ?: $qb->getRootAliases()[0]) . '.organization = :organization')
                ->setParameter('organization', $organization->getId());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function applyOrganizationRestrictionsToAccessRuleCriteria(
        Criteria $criteria,
        ?Organization $organization = null,
        string $organizationFieldName = 'organization'
    ): void {
        if (null === $organization) {
            $organization = $this->tokenAccessor->getOrganization();
        }

        if (null !== $organization) {
            $criteria->andExpression(new Comparison(
                new Path($organizationFieldName, $criteria->getAlias()),
                Comparison::EQ,
                $organization->getId()
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getEnabledOrganizationIds(?Organization $organization = null): array|null
    {
        if (null === $organization) {
            $organization = $this->tokenAccessor->getOrganization();
        }

        if (null === $organization) {
            return [];
        }

        return [$organization->getId()];
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabledOrganization(Organization $organizationToCheck, ?Organization $organization = null): bool
    {
        if (null === $organization) {
            $organization = $this->tokenAccessor->getOrganization();
        }

        return null !== $organization && $organizationToCheck->getId() === $organization->getId();
    }
}
