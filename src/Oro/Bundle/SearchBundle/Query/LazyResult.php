<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Criteria;

/**
 * Allows to pass callbacks instead of the actual values to calculate them separately on demand
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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

    #[\Override]
    public function getElements()
    {
        $this->initializeElements();

        return $this->elements;
    }

    #[\Override]
    public function getRecordsCount()
    {
        if (null === $this->recordsCount) {
            $this->recordsCount = call_user_func($this->recordsCountCallback);
        }

        return $this->recordsCount;
    }

    #[\Override]
    public function getAggregatedData()
    {
        if (null === $this->aggregatedData) {
            $this->aggregatedData = call_user_func($this->aggregatedDataCallback);
        }

        return $this->aggregatedData;
    }

    #[\Override]
    public function toArray()
    {
        $this->initializeElements();

        return parent::toArray();
    }

    #[\Override]
    public function first()
    {
        $this->initializeElements();

        return parent::first();
    }

    #[\Override]
    public function last()
    {
        $this->initializeElements();

        return parent::last();
    }

    #[\Override]
    public function key()
    {
        $this->initializeElements();

        return parent::key();
    }

    #[\Override]
    public function next()
    {
        $this->initializeElements();

        return parent::next();
    }

    #[\Override]
    public function current()
    {
        $this->initializeElements();

        return parent::current();
    }

    #[\Override]
    public function remove($key)
    {
        $this->initializeElements();

        return parent::remove($key);
    }

    #[\Override]
    public function removeElement($element)
    {
        $this->initializeElements();

        return parent::removeElement($element);
    }

    #[\Override]
    public function containsKey($key)
    {
        $this->initializeElements();

        return parent::containsKey($key);
    }

    #[\Override]
    public function contains($element)
    {
        $this->initializeElements();

        return parent::contains($element);
    }

    #[\Override]
    public function exists(\Closure $p)
    {
        $this->initializeElements();

        return parent::exists($p);
    }

    #[\Override]
    public function indexOf($element)
    {
        $this->initializeElements();

        return parent::indexOf($element);
    }

    #[\Override]
    public function get($key)
    {
        $this->initializeElements();

        return parent::get($key);
    }

    #[\Override]
    public function getKeys()
    {
        $this->initializeElements();

        return parent::getKeys();
    }

    #[\Override]
    public function getValues()
    {
        $this->initializeElements();

        return parent::getValues();
    }

    #[\Override]
    public function count(): int
    {
        $this->initializeElements();

        return parent::count();
    }

    #[\Override]
    public function set($key, $value)
    {
        $this->initializeElements();

        parent::set($key, $value);
    }

    #[\Override]
    public function add($element)
    {
        $this->initializeElements();

        return parent::add($element);
    }

    #[\Override]
    public function isEmpty()
    {
        $this->initializeElements();

        return parent::isEmpty();
    }

    /**
     * Required by interface IteratorAggregate.
     *
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        $this->initializeElements();

        return parent::getIterator();
    }

    #[\Override]
    public function map(\Closure $func)
    {
        $this->initializeElements();

        return parent::map($func);
    }

    #[\Override]
    public function filter(\Closure $p)
    {
        $this->initializeElements();

        return parent::filter($p);
    }

    #[\Override]
    public function forAll(\Closure $p)
    {
        $this->initializeElements();

        return parent::forAll($p);
    }

    #[\Override]
    public function partition(\Closure $p)
    {
        $this->initializeElements();

        return parent::partition($p);
    }

    #[\Override]
    public function slice($offset, $length = null)
    {
        $this->initializeElements();

        return parent::slice($offset, $length);
    }

    #[\Override]
    public function matching(Criteria $criteria)
    {
        $this->initializeElements();

        return parent::matching($criteria);
    }
}
