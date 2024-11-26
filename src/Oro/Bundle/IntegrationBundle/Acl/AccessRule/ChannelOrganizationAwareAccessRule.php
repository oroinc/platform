<?php

namespace Oro\Bundle\IntegrationBundle\Acl\AccessRule;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Denies access to the Channel entity that does not belong to the current organization.
 */
class ChannelOrganizationAwareAccessRule implements AccessRuleInterface
{
    public function __construct(
        private TokenAccessorInterface $tokenAccessor,
        private string $organizationFieldName = 'organization'
    ) {
    }

    public function isApplicable(Criteria $criteria): bool
    {
        return $criteria->getEntityClass() === Channel::class;
    }

    public function process(Criteria $criteria): void
    {
        $organization = $this->tokenAccessor->getOrganizationId();
        if (!$organization) {
            $criteria->andExpression(new AccessDenied());

            return;
        }

        $criteria->andExpression(new Comparison(
            new Path($this->organizationFieldName, $criteria->getAlias()),
            Comparison::EQ,
            $organization
        ));
    }
}
