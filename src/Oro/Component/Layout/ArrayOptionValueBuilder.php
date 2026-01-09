<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\Exception\UnexpectedTypeException;

/**
 * Builds array option values by managing a collection of array elements.
 *
 * This builder supports adding, removing, and replacing array elements, with optional support
 * for scalar values that are automatically wrapped in arrays.
 */
class ArrayOptionValueBuilder implements OptionValueBuilderInterface
{
    /** @var bool */
    protected $allowScalarValues = false;

    /** @var array */
    protected $values = [];

    /**
     * @param bool $allowScalarValues Allows to pass scalars as option values
     */
    public function __construct($allowScalarValues = false)
    {
        $this->allowScalarValues = $allowScalarValues;
    }

    /**
     * @param $value
     * @return array
     * @throws UnexpectedTypeException
     */
    protected function prepareValueType($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if ($this->allowScalarValues) {
            return [$value];
        }

        throw new UnexpectedTypeException($value, 'array', 'value');
    }

    #[\Override]
    public function add($value)
    {
        if ($prepared = $this->prepareValueType($value)) {
            $this->values = array_merge($this->values, $prepared);
        }
    }

    #[\Override]
    public function remove($value)
    {
        if (!($prepared = $this->prepareValueType($value))) {
            return;
        }

        // array_diff can't be used because of string conversion
        $this->values = array_values(array_filter($this->values, function ($existingValue) use ($prepared) {
            return !in_array($existingValue, $prepared, true);
        }));
    }

    #[\Override]
    public function replace($oldValues, $newValue)
    {
        if (!$this->prepareValueType($oldValues) || !$this->prepareValueType($newValue)) {
            return;
        }

        if (count($oldValues) !== count($newValue)) {
            throw new InvalidArgumentException(sprintf('$oldValues should be the same as $newValue size.'));
        }

        foreach ($oldValues as $index => $oldValue) {
            $key = array_search($oldValue, $this->values, true);
            if (false !== $key) {
                $this->values[$key] = $newValue[$index];
            }
        }
    }

    #[\Override]
    public function get()
    {
        return $this->values;
    }
}
