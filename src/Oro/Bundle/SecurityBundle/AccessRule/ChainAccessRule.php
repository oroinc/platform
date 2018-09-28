<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

/**
 * Delegates the processing of the criteria to child access rules.
 */
class ChainAccessRule implements AccessRuleInterface
{
    /** @var AccessRuleInterface[] */
    private $rules = [];

    /**
     * Adds new Rule to rules collection.
     *
     * @param AccessRuleInterface $rule
     */
    public function addRule(AccessRuleInterface $rule)
    {
        $this->rules[] = $rule;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        foreach ($this->rules as $rule) {
            if ($rule->isApplicable($criteria)) {
                $rule->process($criteria);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }
}
