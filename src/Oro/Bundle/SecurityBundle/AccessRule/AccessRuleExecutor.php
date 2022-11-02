<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Psr\Container\ContainerInterface;

/**
 * Delegates the processing of the criteria to child access rules.
 */
class AccessRuleExecutor
{
    /** @var array [[service id, [option name => option value, ...]], ...] */
    private $rules;

    /** @var ContainerInterface */
    private $ruleContainer;

    /** @var AccessRuleOptionMatcherInterface */
    private $matcher;

    /**
     * @param string[]                         $rules
     * @param ContainerInterface               $ruleContainer
     * @param AccessRuleOptionMatcherInterface $matcher
     */
    public function __construct(
        array $rules,
        ContainerInterface $ruleContainer,
        AccessRuleOptionMatcherInterface $matcher
    ) {
        $this->rules = $rules;
        $this->ruleContainer = $ruleContainer;
        $this->matcher = $matcher;
    }

    /**
     * Applies access rules to the given criteria object.
     */
    public function process(Criteria $criteria): void
    {
        foreach ($this->rules as list($serviceId, $options)) {
            if (!$this->matches($criteria, $options)) {
                continue;
            }
            /** @var AccessRuleInterface $rule */
            $rule = $this->ruleContainer->get($serviceId);
            if ($rule->isApplicable($criteria)) {
                $rule->process($criteria);
            }
        }
    }

    /**
     * Decides whether an access rule with the given options is applicable for the given criteria object.
     */
    private function matches(Criteria $criteria, array $ruleOptions): bool
    {
        foreach ($ruleOptions as $optionName => $optionValue) {
            if (!$this->matcher->matches($criteria, $optionName, $optionValue)) {
                return false;
            }
        }

        return true;
    }
}
