<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\Exception\InvalidArgumentException;

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
            return array_values($value);
        }

        if ($this->allowScalarValues) {
            return [$value];
        }

        throw new UnexpectedTypeException($value, 'array', 'value');
    }

    /**
     * @inheritdoc
     */
    public function add($value)
    {
        if ($prepared = $this->prepareValueType($value)) {
            $this->values = array_merge($this->values, $prepared);
        }
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function get()
    {
        return $this->values;
    }
}
