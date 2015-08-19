<?php

namespace Oro\Bundle\EmailBundle\Model\Condition;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository;
use Oro\Bundle\WorkflowBundle\Model\Condition\AbstractCondition;

class AutoResponseRulesExists extends AbstractCondition
{
    /** @var Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'auto_response_rules_exists';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return $this->getAutoResponseRuleRepository()->rulesExists();
    }

    /**
     * @return AutoResponseRuleRepository
     */
    protected function getAutoResponseRuleRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:AutoResponseRule');
    }
}
