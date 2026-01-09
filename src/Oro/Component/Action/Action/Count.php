<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Counts elements in an array or countable object and stores the result.
 *
 * This action retrieves a value from the context (which must be an array or implement Countable),
 * counts its elements, and stores the count in a specified attribute. Non-countable values are
 * treated as empty arrays with a count of zero.
 */
class Count extends AbstractAction
{
    /** @var array */
    protected $options = [];

    #[\Override]
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

    #[\Override]
    protected function executeAction($context)
    {
        $value = $this->contextAccessor->getValue($context, $this->options['value']);
        if (!is_array($value) && !$value instanceof \Countable) {
            $value = [];
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], count($value));
    }
}
