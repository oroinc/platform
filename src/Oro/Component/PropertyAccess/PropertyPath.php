<?php

namespace Oro\Component\PropertyAccess;

use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\PropertyAccess\PropertyPathIterator;

class PropertyPath implements \IteratorAggregate, PropertyPathInterface
{
    /** @var string */
    protected $path;

    /** @var array */
    protected $elements;

    /**
     * Contains a Boolean for each property in $elements denoting whether this
     * element is an index. It is a property otherwise.
     *
     * @var array
     */
    private $isIndex = array();

    /**
     * @param string $path
     *
     * @throws Exception\InvalidPropertyPathException if a property path is invalid
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __construct($path)
    {
        if (!is_string($path)) {
            throw new Exception\InvalidPropertyPathException(
                sprintf(
                    'Expected argument of type "string", "%s" given.',
                    is_object($path) ? get_class($path) : gettype($path)
                )
            );
        }
        if ($path === '') {
            throw new Exception\InvalidPropertyPathException('The property path must not be empty.');
        }

        $this->path     = $path;
        $this->elements = [];

        $remaining = $this->path;
        $pos       = 0;

        // first element is evaluated differently - no leading dot for properties
        if (preg_match('/^(([^\.\[]+)|\[([^\]]+)\])(.*)/', $remaining, $matches)) {
            $this->elements[] = $matches[2] === '' ? $matches[3] : $matches[2];
            $this->isIndex[]  = $matches[2] === '' ? true : false;

            $pos       = strlen($matches[1]);
            $remaining = $matches[4];
            $pattern   = '/^(\.([^\.|\[]+)|\[([^\]]+)\])(.*)/';
            while (preg_match($pattern, $remaining, $matches)) {
                $this->elements[] = $matches[2] === '' ? $matches[3] : $matches[2];
                $this->isIndex[]  = $matches[2] === '' ? true : false;

                $pos += strlen($matches[1]);
                $remaining = $matches[4];
            }
        }

        if ($remaining !== '') {
            $this->elements = null;
            throw new Exception\InvalidPropertyPathException(
                sprintf(
                    'Could not parse property path "%s". Unexpected token "%s" at position %d.',
                    $this->path,
                    $remaining[0],
                    $pos
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->path;
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
            return;
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
