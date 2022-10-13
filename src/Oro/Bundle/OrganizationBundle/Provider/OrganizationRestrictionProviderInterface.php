<?php

namespace Oro\Bundle\OrganizationBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;

/**
 * Represents a service to apply organization related restrictions.
 */
interface OrganizationRestrictionProviderInterface
{
    /**
     * Applies organization related restrictions to the given query builder.
     *
     * @param QueryBuilder      $qb           The query builder that should be updated to apply the restrictions
     * @param Organization|null $organization An organization that represents the current security context
     * @param string|null       $entityAlias  An alias of an entity for which the restriction should be applies
     */
    public function applyOrganizationRestrictions(
        QueryBuilder $qb,
        ?Organization $organization = null,
        ?string $entityAlias = null
    ): void;

    /**
     * Applies organization related restrictions to the given access rule criteria.
     *
     * @param Criteria          $criteria              The criteria that should be updated to apply the restrictions
     * @param Organization|null $organization          An organization that represents the current security context
     * @param string            $organizationFieldName The name of a field that represents
     *                                                 an association to an organization
     */
    public function applyOrganizationRestrictionsToAccessRuleCriteria(
        Criteria $criteria,
        ?Organization $organization = null,
        string $organizationFieldName = 'organization'
    ): void;

    /**
     * Gets IDs of enabled organizations.
     *
     * @param Organization|null $organization An organization that represents the current security context
     *
     * @return int[]|null The list of enabled organization IDs
     *                    or NULL if restrictions by organization should not be applied
     */
    public function getEnabledOrganizationIds(?Organization $organization = null): array|null;

    /**
     * Checks whether the given organization is enabled or not.
     *
     * @param Organization      $organizationToCheck An organization to be checked
     * @param Organization|null $organization        An organization that represents the current security context
     *
     * @return bool
     */
    public function isEnabledOrganization(Organization $organizationToCheck, ?Organization $organization = null): bool;
}
