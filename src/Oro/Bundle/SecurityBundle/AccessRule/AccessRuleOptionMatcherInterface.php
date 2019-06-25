<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

/**
 * Provides an interface for classes that can check whether access rules are applicable for a criteria object.
 */
interface AccessRuleOptionMatcherInterface
{
    /**
     * Decides whether an access rule with the given option is applicable for the given criteria object.
     *
     * @param Criteria $criteria
     * @param string   $optionName
     * @param mixed    $optionValue
     *
     * @return bool
     */
    public function matches(Criteria $criteria, string $optionName, $optionValue): bool;
}
