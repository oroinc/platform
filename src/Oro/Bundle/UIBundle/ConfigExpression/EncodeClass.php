<?php

namespace Oro\Bundle\UIBundle\ConfigExpression;

use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Func\AbstractFunction;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

/**
 * Implements an expression that encodes the class name into the format that can be used in route parameters.
 */
class EncodeClass extends AbstractFunction
{
    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var mixed */
    protected $value;

    /**
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function __construct(EntityRoutingHelper $entityRoutingHelper)
    {
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'encode_class';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->value, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 === count($options)) {
            $this->value = reset($options);
        } else {
            throw new InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ value }}' => $this->resolveValue($context, $this->value)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        $value = $this->resolveValue($context, $this->value);
        if ($value === null) {
            return $value;
        }
        if (!is_string($value)) {
            $this->addError(
                $context,
                sprintf(
                    'Expected a string value, got "%s".',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );

            return $value;
        }

        return $this->entityRoutingHelper->encodeClassName($value);
    }
}
