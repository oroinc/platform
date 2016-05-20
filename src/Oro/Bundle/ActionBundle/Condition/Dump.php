<?php

namespace Oro\Bundle\ActionBundle\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;

use Oro\Bundle\ActionBundle\Model\ActionData;

class Dump extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'dump';

    protected $options = [];

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) < 1) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 or more elements, but %d given.', count($options))
            );
        }

        $this->options = $options;

        return $this;
    }

    protected function isConditionAllowed($context)
    {
        /* @var $context ActionData */
        $options = [];
        foreach ($this->options as $key => $option) {
            $options[$key] = $this->resolveValue($context, $option, false);
        }

        $result = array_shift($options);


        error_log('action.DEBUG: ' . json_encode($options ?: $this->getContextValues($context)));

        return $result;
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getContextValues($context)
    {
        $values = [];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->getContextValues($value);
            } elseif (is_scalar($value)) {
                $values[$key] = $value;
            } elseif (is_object($value)) {
                $values[$key] = '(object)' . get_class($value);
            }
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
