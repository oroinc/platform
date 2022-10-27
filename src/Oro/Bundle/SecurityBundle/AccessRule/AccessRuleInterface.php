<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

/**
 * Represents a rule that is used to determine whether an access to an entity is granted or not.
 */
interface AccessRuleInterface
{
    /**
     * Checks whether this rule can be applied to the given criteria.
     * Note: this method is intended for complex logic that cannot be achieved
     * via the "oro_security.access_rule" tag options.
     * @link ../Resources/doc/access-rules.md#add-a-new-access-rule
     */
    public function isApplicable(Criteria $criteria): bool;

    /**
     * Adds necessary access expressions to the given criteria.
     */
    public function process(Criteria $criteria): void;
}
