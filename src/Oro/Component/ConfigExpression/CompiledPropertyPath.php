<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\Exception;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\PropertyAccess\PropertyPathIterator;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;

/**
 * Compiled sequence of property names or array indices
 */
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

    #[\Override]
    public function __toString(): string
    {
        return (string)$this->path;
    }

    #[\Override]
    public function getLength(): int
    {
        return count($this->elements);
    }

    #[\Override]
    public function getParent(): ?PropertyPathInterface
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
     */
    #[\Override]
    public function getIterator(): PropertyPathIteratorInterface
    {
        return new PropertyPathIterator($this);
    }

    #[\Override]
    public function getElements(): array
    {
        return $this->elements;
    }

    #[\Override]
    public function getElement($index): string
    {
        if (!isset($this->elements[$index])) {
            throw new Exception\OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->elements[$index];
    }

    #[\Override]
    public function isProperty($index): bool
    {
        if (!isset($this->isIndex[$index])) {
            throw new Exception\OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return !$this->isIndex[$index];
    }

    #[\Override]
    public function isIndex($index): bool
    {
        if (!isset($this->isIndex[$index])) {
            throw new Exception\OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->isIndex[$index];
    }
}
