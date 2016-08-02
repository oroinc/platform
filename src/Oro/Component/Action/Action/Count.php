<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\InvalidParameterException;

class Count extends AbstractAction
{
    /** @var array */
    protected $options = [];

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('value', $options)) {
            $this->options['value'] = $options['value'];
        }

        if (array_key_exists('attribute', $options)) {
            $this->options['attribute'] = $options['attribute'];
        }

        //validation
        if (!array_key_exists('value', $this->options)) {
            throw new InvalidParameterException('Parameter `value` is required.');
        }

        if (empty($this->options['attribute'])) {
            throw new InvalidParameterException('Parameter `attribute` is required.');
        }

        if (!$this->options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Parameter `attribute` must be a valid property definition.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $value = $this->contextAccessor->getValue($context, $this->options['value']);
        if (!is_array($value) && !$value instanceof \Countable) {
            $value = [];
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], count($value));
    }
}
