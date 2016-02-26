<?php

namespace Oro\Bundle\EmailBundle\Model\Condition;

use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

/**
 * Examples:
 * @instanceof: [$.object, Some\Class\FQCN]
 * @instanceof:
 *     object: $.object
 *     class: Some\Class\FQCN
 *
 * Class IsInstanceOf
 * @package Oro\Bundle\EmailBundle\Model\Condition
 */
class IsInstanceOf extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var string */
    protected $object;

    /** @var string */
    protected $class;

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $object = $this->resolveValue($context, $this->object);
        $class = $this->resolveValue($context, $this->class);

        return ($object instanceof $class);
    }

    /**
     * Returns the expression name.
     *
     * @return string
     */
    public function getName()
    {
        return 'instanceof';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (2 !== count($options)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 2 elements, but %d given.', count($options))
            );
        }

        if (isset($options['object'])) {
            $this->object = $options['object'];
        } elseif (isset($options[0])) {
            $this->object = $options[0];
        } else {
            throw new Exception\InvalidArgumentException('Option "object" is required.');
        }

        if (isset($options['class'])) {
            $this->class = $options['class'];
        } elseif (isset($options[1])) {
            $this->class = $options[1];
        } else {
            throw new Exception\InvalidArgumentException('Option "class" is required.');
        }

        return $this;
    }
}
