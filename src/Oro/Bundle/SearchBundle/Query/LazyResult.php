<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Criteria;

/**
 * Allows to pass callbacks instead of the actual values to calculate them separately on demand
 */
class LazyResult extends Result
{
    /** @var \Closure */
    protected $elementsCallback;

    /** @var \Closure */
    protected $recordsCountCallback;

    /** @var \Closure */
    protected $aggregatedDataCallback;

    /**
     * @param Query            $query
     * @param \Closure|array   $elements
     * @param \Closure|integer $recordsCount
     * @param \Closure|array   $aggregatedData
     */
    public function __construct(
        Query $query,
        $elements = [],
        $recordsCount = 0,
        $aggregatedData = []
    ) {
        $this->query = $query;

        if ($elements instanceof \Closure) {
            $this->elementsCallback = $elements;
        } else {
            $this->elements = $elements;
            $this->fillCollectionElements($this->elements);
        }

        if ($recordsCount instanceof \Closure) {
            $this->recordsCountCallback = $recordsCount;
        } else {
            $this->recordsCount = $recordsCount;
        }

        if ($aggregatedData instanceof \Closure) {
            $this->aggregatedDataCallback = $aggregatedData;
        } else {
            $this->aggregatedData = $aggregatedData;
        }

        // parent constructor is not called intentionally to make sure that properties will not be initialized
    }

    /**
     * Fill internal storage of ArrayCollection to maintain compatibility with Collection interface
     *
     * @param array $elements
     */
    protected function fillCollectionElements(array $elements)
    {
        parent::clear();

        foreach ($elements as $key => $value) {
            parent::set($key, $value);
        }
    }

    /**
     * Loads elements using callback
     */
    protected function initializeElements()
    {
        if (null === $this->elements) {
            $this->elements = call_user_func($this->elementsCallback);
            $this->fillCollectionElements($this->elements);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getElements()
    {
        $this->initializeElements();

        return $this->elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecordsCount()
    {
        if (null === $this->recordsCount) {
            $this->recordsCount = call_user_func($this->recordsCountCallback);
        }

        return $this->recordsCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregatedData()
    {
        if (null === $this->aggregatedData) {
            $this->aggregatedData = call_user_func($this->aggregatedDataCallback);
        }

        return $this->aggregatedData;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->initializeElements();

        return parent::toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function first()
    {
        $this->initializeElements();

        return parent::first();
    }

    /**
     * {@inheritDoc}
     */
    public function last()
    {
        $this->initializeElements();

        return parent::last();
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        $this->initializeElements();

        return parent::key();
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->initializeElements();

        return parent::next();
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        $this->initializeElements();

        return parent::current();
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key)
    {
        $this->initializeElements();

        return parent::remove($key);
    }

    /**
     * {@inheritDoc}
     */
    public function removeElement($element)
    {
        $this->initializeElements();

        return parent::removeElement($element);
    }

    /**
     * {@inheritDoc}
     */
    public function containsKey($key)
    {
        $this->initializeElements();

        return parent::containsKey($key);
    }

    /**
     * {@inheritDoc}
     */
    public function contains($element)
    {
        $this->initializeElements();

        return parent::contains($element);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(\Closure $p)
    {
        $this->initializeElements();

        return parent::exists($p);
    }

    /**
     * {@inheritDoc}
     */
    public function indexOf($element)
    {
        $this->initializeElements();

        return parent::indexOf($element);
    }

    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        $this->initializeElements();

        return parent::get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getKeys()
    {
        $this->initializeElements();

        return parent::getKeys();
    }

    /**
     * {@inheritDoc}
     */
    public function getValues()
    {
        $this->initializeElements();

        return parent::getValues();
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        $this->initializeElements();

        return parent::count();
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        $this->initializeElements();

        parent::set($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function add($element)
    {
        $this->initializeElements();

        return parent::add($element);
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        $this->initializeElements();

        return parent::isEmpty();
    }

    /**
     * Required by interface IteratorAggregate.
     *
     * {@inheritDoc}
     */
    public function getIterator()
    {
        $this->initializeElements();

        return parent::getIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function map(\Closure $func)
    {
        $this->initializeElements();

        return parent::map($func);
    }

    /**
     * {@inheritDoc}
     */
    public function filter(\Closure $p)
    {
        $this->initializeElements();

        return parent::filter($p);
    }

    /**
     * {@inheritDoc}
     */
    public function forAll(\Closure $p)
    {
        $this->initializeElements();

        return parent::forAll($p);
    }

    /**
     * {@inheritDoc}
     */
    public function partition(\Closure $p)
    {
        $this->initializeElements();

        return parent::partition($p);
    }

    /**
     * {@inheritDoc}
     */
    public function slice($offset, $length = null)
    {
        $this->initializeElements();

        return parent::slice($offset, $length);
    }

    /**
     * {@inheritDoc}
     */
    public function matching(Criteria $criteria)
    {
        $this->initializeElements();

        return parent::matching($criteria);
    }
}
