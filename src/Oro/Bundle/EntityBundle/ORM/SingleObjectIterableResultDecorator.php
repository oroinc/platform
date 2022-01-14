<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Internal\Hydration\HydrationException;
use Doctrine\ORM\Internal\Hydration\IterableResult;

/**
 * This class is helpful when there's a query iterator which iterates through array of objects.
 * It decorates iterator in such way that result of each iteration is an object instead of array with one element
 * which contains resulting object.
 */
class SingleObjectIterableResultDecorator implements \Iterator
{
    /**
     * @var IterableResult
     */
    protected $iterableResult;

    public function __construct(IterableResult $iterableResult)
    {
        $this->iterableResult = $iterableResult;
    }

    /**
     * {@inheritdoc}
     *
     * @throws HydrationException
     */
    public function rewind(): void
    {
        $this->iterableResult->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->iterableResult->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        $current = $this->iterableResult->current();
        return is_iterable($current)? reset($current):$current;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->iterableResult->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->iterableResult->valid();
    }
}
