<?php

namespace Oro\Bundle\IntegrationBundle\Model\Condition;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Exception\ConditionException;
use Oro\Bundle\WorkflowBundle\Model\Condition\AbstractCondition;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

/**
 * Check For Active integration of given type
 * Usage:
 * @has_active_integration: 'some_type'
 */
class HasActiveIntegration extends AbstractCondition
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PropertyPath|String
     */
    protected $type;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     */
    public function __construct(ContextAccessor $contextAccessor, ManagerRegistry $registry)
    {
        $this->contextAccessor = $contextAccessor;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $type = $this->contextAccessor->getValue($context, $this->type);

        return (bool)$this->getActiveIntegration($type);
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getActiveIntegration($type)
    {
        return $this->registry->getRepository('OroIntegrationBundle:Channel')
            ->getConfiguredChannelsForSync($type, true);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 == count($options)) {
            $this->type = reset($options);
        } else {
            throw new ConditionException(
                sprintf(
                    'Options must have 1 element, but %d given',
                    count($options)
                )
            );
        }

        return $this;
    }
}
