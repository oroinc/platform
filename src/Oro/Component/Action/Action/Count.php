<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class Count extends AbstractAction
{
    /** @var array */
    protected $options = [];

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!isset($options['value'])) {
            throw new InvalidParameterException('Parameter `value` is required.');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Parameter `attribute` is required.');
        }
        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Parameter `attribute` must be a valid property definition.');
        }

        $this->options = $options;

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
