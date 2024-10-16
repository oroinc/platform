<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

/**
 * Denies access to an entity when an access to an associated entity is denied.
 */
class AssociationAwareAccessRule implements AccessRuleInterface
{
    public function __construct(
        private readonly string $associationName
    ) {
    }

    #[\Override]
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    #[\Override]
    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(new Association($this->associationName));
    }
}
