<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Psr\Container\ContainerInterface;

/**
 * Delegates the processing of the criteria to child access rules.
 */
class AccessRuleExecutor
{
    /** @var string[] */
    private $ruleNames;

    /** @var ContainerInterface */
    private $ruleContainer;

    /**
     * @param string[]           $ruleNames
     * @param ContainerInterface $ruleContainer
     */
    public function __construct(array $ruleNames, ContainerInterface $ruleContainer)
    {
        $this->ruleNames = $ruleNames;
        $this->ruleContainer = $ruleContainer;
    }

    /**
     * Applies access rules to the given criteria object.
     *
     * @param Criteria $criteria
     */
    public function process(Criteria $criteria): void
    {
        foreach ($this->ruleNames as $ruleName) {
            /** @var AccessRuleInterface $rule */
            $rule = $this->ruleContainer->get($ruleName);
            if ($rule->isApplicable($criteria)) {
                $rule->process($criteria);
            }
        }
    }
}
