<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

/**
 * Represents a rule that is used to determine whether an access to an entity is granted or not.
 */
interface AccessRuleInterface
{
    /**
     * Checks whether this rule can be applied to the given criteria.
     *
     * @param Criteria $criteria
     *
     * @return bool
     */
    public function isApplicable(Criteria $criteria): bool;

    /**
     * Adds necessary access expressions to the given criteria.
     *
     * @param Criteria $criteria
     */
    public function process(Criteria $criteria): void;
}
