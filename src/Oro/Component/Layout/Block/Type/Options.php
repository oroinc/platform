<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\ExpressionLanguage\Expression;

/**
 * Block type option DTO.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Options implements \ArrayAccess, \Iterator
{
    /** @var array */
    private $options = [];

    public function __construct(array $data = [])
    {
        $this->setMultiple($data);
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
                    sprintf(
                        'Option "%s" value should be evaluated. It is currently '.
                        'an ExpressionLanguage component object.',
                        $offset
                    )
                );
            }
            return $option;
        }

        throw new \OutOfBoundsException(sprintf('Argument "%s" not found.', $offset));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): mixed
    {
        return $this->offsetExists($offset) ? $this->get($offset) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
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
    public function offsetUnset($offset): void
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

    public function setMultiple(array $data)
    {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }
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
    public function rewind(): void
    {
        reset($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        return current($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return key($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        next($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return key($this->options) !== null;
    }
}
