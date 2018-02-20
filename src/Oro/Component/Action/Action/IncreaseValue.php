<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Increase or decrease the integer value by some value
 *
 * Usage:
 * @increase_value:
 *     attribute: $.some_value
 *     value: 5
 *
 * OR
 *
 * @increase_value:
 *     attribute: $.some_value
 *     value: -5
 *
 * OR
 *
 * @increase_value: [$.some_value, 5]
 *
 * OR
 *
 * @increase_value: $.some_value
 */
class IncreaseValue extends AbstractAction
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $count = count($options);

        if ($count < 1) {
            throw new InvalidParameterException('Attribute parameter is required.');
        }

        if (!isset($options['attribute']) && !isset($options[0])) {
            throw new InvalidParameterException('Attribute must be defined.');
        }

        if (!($this->getAttribute($options) instanceof PropertyPathInterface)) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        $value = $this->getValue($options);
        if (!is_int($value)) {
            throw new InvalidParameterException('Value must be integer.');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    protected function getAttribute(array $options)
    {
        return array_key_exists('attribute', $options) ? $options['attribute'] : $options[0];
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    protected function getValue(array $options)
    {
        $value = 1;
        if (isset($options['value'])) {
            $value = $options['value'];
        } elseif (isset($options[1])) {
            $value = $options[1];
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $attribute = $this->getAttribute($this->options);
        $value = $this->getValue($this->options);

        $result = (int)$this->contextAccessor->getValue($context, $attribute);
        $result += (int)$this->contextAccessor->getValue($context, $value);

        $this->contextAccessor->setValue($context, $attribute, $result);
    }
}
