<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception\UnexpectedTypeException;

class StringOptionValueBuilder implements OptionValueBuilderInterface
{
    /** @var string */
    protected $delimiter;

    /** @var bool */
    protected $allowTokenize;

    /** @var string[] */
    protected $values = [];

    /**
     * @param string $delimiter     The delimiter of values
     * @param bool   $allowTokenize Determines whether a value for add and replace methods
     *                              should be split by $delimiter before processing
     */
    public function __construct($delimiter = ' ', $allowTokenize = true)
    {
        if (!is_string($delimiter)) {
            throw new UnexpectedTypeException($delimiter, 'string', 'delimiter');
        }

        $this->delimiter     = $delimiter;
        $this->allowTokenize = empty($delimiter) ? false : $allowTokenize;
    }

    /**
     * Requests to add new value
     *
     * @param string $value
     */
    public function add($value)
    {
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string', 'value');
        }
        if (empty($value)) {
            return;
        }

        if ($this->allowTokenize) {
            $this->values = array_merge($this->values, explode($this->delimiter, $value));
        } else {
            $this->values[] = $value;
        }
    }

    /**
     * Requests to remove existing value
     *
     * @param string $value
     */
    public function remove($value)
    {
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string', 'value');
        }
        if (empty($value)) {
            return;
        }

        if ($this->allowTokenize) {
            $this->values = array_diff($this->values, explode($this->delimiter, $value));
        } else {
            $this->values = array_diff($this->values, [$value]);
        }
    }

    /**
     * Requests to replace one value with another value
     *
     * @param string      $oldValue
     * @param string|null $newValue
     */
    public function replace($oldValue, $newValue)
    {
        if (!is_string($oldValue)) {
            throw new UnexpectedTypeException($oldValue, 'string', 'oldValue');
        }
        if (empty($oldValue)) {
            return;
        }
        if (!is_string($newValue) && null !== $newValue) {
            throw new UnexpectedTypeException($newValue, 'string or null', 'newValue');
        }

        $key = array_search($oldValue, $this->values, true);
        if (false !== $key) {
            if (empty($newValue)) {
                unset($this->values[$key]);
            } else {
                $this->values[$key] = $newValue;
            }
        }
    }

    /**
     * Returns the built string
     *
     * @return string
     */
    public function get()
    {
        return implode($this->delimiter, $this->values);
    }
}
