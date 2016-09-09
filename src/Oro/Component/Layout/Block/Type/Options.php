<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class Options implements \ArrayAccess, \Iterator
{
    /** @var array */
    private $options = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * @param string $offset
     * @param boolean $shouldBeEvaluated
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException
     */
    public function get($offset, $shouldBeEvaluated = true)
    {
        if (array_key_exists($offset, $this->options)) {
            $option = $this->options[$offset];
            if ($shouldBeEvaluated && $option instanceof Expression) {
                throw new \InvalidArgumentException(
                    sprintf('Option "%s" should be evaluated but expression now.', $offset)
                );
            }
            return $option;
        }

        throw new \OutOfBoundsException(sprintf('Argument "%s" not found.', $offset));
    }

    public function getOr($offset, $default = null)
    {
        try {
            return $this->get($offset, false);
        } catch (\OutOfBoundsException $e) {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->get($offset) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (is_array($value)) {
            $value = new self($value);
        }
        if ($offset === null) {
            $this->options[] = $value;
        } else {
            $this->options[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->options)) {
            unset($this->options[$offset]);
        }
    }

    /**
     * @internal
     * @return mixed
     */
    public function getAll()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = $this->options;
        foreach ($data as $key => $value) {
            if ($value instanceof self) {
                $data[$key] = $value->toArray();
            }
        }

        return $data;
    }

    /**
     * @param $offset
     *
     * @return boolean
     */
    public function isExistsAndNotEmpty($offset)
    {
        return $this->offsetExists($offset) && $this->offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return reset($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        return next($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return key($this->options) !== null;
    }
}
