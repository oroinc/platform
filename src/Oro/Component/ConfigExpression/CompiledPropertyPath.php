<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\Exception;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\PropertyAccess\PropertyPathIterator;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;

final class CompiledPropertyPath implements \IteratorAggregate, PropertyPathInterface
{
    /** @var string */
    private $path;

    /**
     * The elements of the property path.
     *
     * @var array
     */
    private $elements = [];

    /**
     * Contains a Boolean for each property in $elements denoting whether this
     * element is an index. It is a property otherwise.
     *
     * @var bool[]
     */
    private $isIndex = [];

    /**
     * @param string $path
     * @param array  $elements
     * @param bool[] $isIndex
     */
    public function __construct($path, array $elements, array $isIndex)
    {
        $this->path     = $path;
        $this->elements = $elements;
        $this->isIndex  = $isIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getLength()
    {
        return count($this->elements);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if ($this->getLength() <= 1) {
            return null;
        }

        $parent = clone $this;

        $parent->path = substr($parent->path, 0, max(strrpos($parent->path, '.'), strrpos($parent->path, '[')));
        array_pop($parent->elements);
        array_pop($parent->isIndex);

        return $parent;
    }

    /**
     * Returns a new iterator for this path.
     *
     * @return PropertyPathIteratorInterface
     */
    public function getIterator()
    {
        return new PropertyPathIterator($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getElement($index)
    {
        if (!isset($this->elements[$index])) {
            throw new Exception\OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->elements[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function isProperty($index)
    {
        if (!isset($this->isIndex[$index])) {
            throw new Exception\OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return !$this->isIndex[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function isIndex($index)
    {
        if (!isset($this->isIndex[$index])) {
            throw new Exception\OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->isIndex[$index];
    }
}
