<?php

namespace Oro\Bundle\OrganizationBundle\Acl\AccessRule;

use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;

/**
 * Denies access to entities that do not belong to the current organization.
 */
class OrganizationAwareAccessRule implements AccessRuleInterface
{
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;
    private string $organizationFieldName;

    public function __construct(
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider,
        string $organizationFieldName = 'organization'
    ) {
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
        $this->organizationFieldName = $organizationFieldName;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function process(Criteria $criteria): void
    {
        $this->organizationRestrictionProvider->applyOrganizationRestrictionsToAccessRuleCriteria(
            $criteria,
            null,
            $this->organizationFieldName
        );
    }
}
