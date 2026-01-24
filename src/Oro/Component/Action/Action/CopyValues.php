<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Copies values from multiple sources into a target attribute.
 *
 * This action merges data from one or more source arrays or objects into a target attribute.
 * It supports copying from both array and object sources, and can handle multiple data sources
 * in a single execution. The target attribute can be either an array or an object.
 */
class CopyValues extends AbstractAction
{
    /** @var PropertyPathInterface */
    protected $attribute;

    /** @var array */
    protected $options = [];

    #[\Override]
    protected function executeAction($context)
    {
        $attribute = $this->contextAccessor->getValue($context, $this->attribute) ?: [];

        foreach ($this->options as $data) {
            if ($data instanceof PropertyPathInterface) {
                $data = $this->contextAccessor->getValue($context, $data);
            }

            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $key => $value) {
                if (is_object($attribute)) {
                    $this->contextAccessor->setValue($attribute, $key, $value);
                } else {
                    $attribute[$key] = $value;
                }
            }
        }

        $this->contextAccessor->setValue($context, $this->attribute, $attribute);
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (count($options) < 2) {
            throw new InvalidParameterException('Attribute and data parameters are required.');
        }

        if (!$options[0] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        $this->attribute = array_shift($options);
        $this->options = $options;

        return $this;
    }
}
