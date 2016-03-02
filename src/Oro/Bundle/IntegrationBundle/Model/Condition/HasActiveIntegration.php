<?php

namespace Oro\Bundle\IntegrationBundle\Model\Condition;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check For Active integration of given type
 * Usage:
 * @has_active_integration: 'some_type'
 */
class HasActiveIntegration extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PropertyPath|String
     */
    protected $type;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'has_active_integration';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $type = $this->resolveValue($context, $this->type, false);

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
            throw new InvalidArgumentException(
                sprintf(
                    'Options must have 1 element, but %d given',
                    count($options)
                )
            );
        }

        return $this;
    }
}
