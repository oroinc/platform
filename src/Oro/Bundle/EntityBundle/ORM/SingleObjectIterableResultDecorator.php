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

    /**
     * @param IterableResult $iterableResult
     */
    public function __construct(IterableResult $iterableResult)
    {
        $this->iterableResult = $iterableResult;
    }

    /**
     * {@inheritdoc}
     *
     * @throws HydrationException
     */
    public function rewind()
    {
        $this->iterableResult->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $next = $this->iterableResult->next();
        return is_array($next)
            ? reset($next)
            : $next;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $current = $this->iterableResult->current();
        return reset($current);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterableResult->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->iterableResult->valid();
    }
}
