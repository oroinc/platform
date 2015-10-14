<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

/**
 * Iterates results of Query using buffer, allows to iterate large query
 * results without risk of getting out of memory error
 */
class BufferedQueryResultIterator implements \Iterator, \Countable
{
    /**
     * Count of records that will be loaded on each page during iterations
     */
    const DEFAULT_BUFFER_SIZE = 200;

    /**
     * Count of records that will be loaded on each page during iterations
     * This is just recommended buffer size because the real size can be differed
     * in case when MaxResults of source query is specified
     *
     * @var int
     */
    private $requestedBufferSize = self::DEFAULT_BUFFER_SIZE;

    /**
     * Defines the processing mode to be used during hydration / result set transformation
     * This is just recommended hydration mode because the real mode can be calculated automatically
     * in case when the requested hydration mode is not specified
     *
     * @var integer
     */
    private $requestedHydrationMode;

    /**
     * The source Query or QueryBuilder
     *
     * @var mixed
     */
    private $source;

    /**
     * Query to iterate
     *
     * @var Query
     */
    private $query;

    /**
     * Total count of records in query
     *
     * @var int
     */
    private $totalCount;

    /**
     * Index of current page
     *
     * @var int
     */
    private $page = -1;

    /**
     * Offset of current record in current page
     *
     * @var int
     */
    private $offset = -1;

    /**
     * A position of a current record within the current page
     *
     * @var int
     */
    private $position = -1;

    /**
     * Rows that where loaded for current page
     *
     * @var array
     */
    private $rows;

    /**
     * Current record, populated from query result row
     *
     * @var mixed
     */
    private $current;

    /**
     * @var int
     */
    protected $firstResult;

    /**
     * The maximum number of results the original query object was set to retrieve
     *
     * @var int
     */
    protected $maxResults;

    /**
     * @var bool|null
     */
    private $useCountWalker;

    /**
     * Walk through results in reverse order
     * Useful when selected records are being updated in between page load
     *
     * @var bool
     */
    private $reverse = false;

    /**
     * Constructor
     *
     * @param Query|QueryBuilder $source
     * @param null|bool $useCountWalker
     */
    public function __construct($source, $useCountWalker = null)
    {
        if (null === $source) {
            throw new \InvalidArgumentException('The $source must not be null');
        } elseif (!($source instanceof Query) && !($source instanceof QueryBuilder)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The $source must be instance of "%s" or "%s", but "%s" given',
                    is_object($this->source) ? get_class($this->source) : gettype($this->source),
                    'Doctrine\ORM\Query',
                    'Doctrine\ORM\QueryBuilder'
                )
            );
        }
        $this->source = $source;
        $this->useCountWalker = $useCountWalker;
    }

    /**
     * Sets size of buffer that is queried from storage to iterate results
     *
     * @param int $bufferSize
     * @return BufferedQueryResultIterator
     * @throws \InvalidArgumentException If buffer size is not greater than 0
     */
    public function setBufferSize($bufferSize)
    {
        $this->assertQueryWasNotExecuted('buffer size');
        if ($bufferSize <= 0) {
            throw new \InvalidArgumentException('$bufferSize must be greater than 0');
        }
        $this->requestedBufferSize = (int)$bufferSize;

        return $this;
    }

    /**
     * Sets query hydration mode to be used to iterate results
     *
     * @param integer $hydrationMode Processing mode to be used during the hydration process.
     * @return BufferedQueryResultIterator
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->assertQueryWasNotExecuted('hydration mode');
        $this->requestedHydrationMode = $hydrationMode;

        return $this;
    }

    /**
     * Sets iteration order
     *
     * @param bool $reverse Determines the iteration order
     * @return BufferedQueryResultIterator
     */
    public function setReverse($reverse)
    {
        $this->assertQueryWasNotExecuted('reverse mode');
        $this->reverse = $reverse;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->offset++;

        if (!isset($this->rows[$this->offset]) && !$this->loadNextPage()) {
            $this->current = null;
        } else {
            $this->current  = $this->rows[$this->offset];
            $this->position = $this->offset + $this->getQuery()->getMaxResults() * $this->page;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return null !== $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        // reset total count only if at least one item was loaded by this iterator
        // for example if we call count method and then start iteration the total count must be calculated once
        if (null !== $this->totalCount && $this->offset != -1) {
            $this->totalCount = null;
        }
        $this->offset     = -1;
        $this->page       = -1;
        $this->position   = -1;
        $this->current    = null;

        $this->next();
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        if (null === $this->totalCount) {
            $query = $this->cloneQuery($this->getQuery());
            // restore original max results
            $query->setMaxResults($this->maxResults);

            $this->totalCount = QueryCountCalculator::calculateCount($query, $this->useCountWalker);
        }

        return $this->totalCount;
    }

    /**
     * Asserts that query was not executed, otherwise raise an exception
     *
     * @param string $optionLabel
     * @throws \LogicException
     */
    protected function assertQueryWasNotExecuted($optionLabel)
    {
        if (!$this->source) {
            throw new \LogicException(sprintf('Cannot set %s object as query was already executed.', $optionLabel));
        }
    }

    /**
     * @return Query
     * @throws \LogicException If source of a query is not valid
     */
    protected function getQuery()
    {
        if (null === $this->query) {
            if ($this->source instanceof Query) {
                $this->query = $this->cloneQuery($this->source);
            } elseif ($this->source instanceof QueryBuilder) {
                $this->query = $this->source->getQuery();
            } else {
                throw new \LogicException('Unexpected source');
            }
            unset($this->source);

            // initialize cloned query
            $this->maxResults = $this->query->getMaxResults();
            if (!$this->maxResults || $this->requestedBufferSize < $this->maxResults) {
                $this->query->setMaxResults($this->requestedBufferSize);
            }
            if (null !== $this->requestedHydrationMode) {
                $this->query->setHydrationMode($this->requestedHydrationMode);
            }
            $this->firstResult = (int)$this->query->getFirstResult();
        }

        return $this->query;
    }

    /**
     * Makes full clone of the given query, including its parameters and hints
     *
     * @param Query $query
     * @return Query
     */
    protected function cloneQuery(Query $query)
    {
        $result = clone $query;

        // clone parameters
        $result->setParameters(clone $query->getParameters());

        // clone hints
        foreach ($query->getHints() as $name => $value) {
            $result->setHint($name, $value);
        }

        return $result;
    }

    /**
     * Attempts to load next page
     *
     * @return bool If page loaded successfully
     */
    protected function loadNextPage()
    {
        $query = $this->getQuery();

        $totalPages = ceil($this->count() / $query->getMaxResults());
        if ($this->reverse) {
            if ($this->page == -1) {
                $this->page = $totalPages;
            }
            if ($this->page < 1) {
                unset($this->rows);

                return false;
            }
            $this->page--;
        } else {
            if (!$totalPages || $totalPages <= $this->page + 1) {
                unset($this->rows);

                return false;
            }
            $this->page++;
        }

        $this->offset = 0;

        $this->prepareQueryToExecute($query);
        $this->rows = $query->execute();

        return count($this->rows) > 0;
    }

    /**
     * Makes final preparation of a query object before its execute method will be called.
     *
     * @param Query $query
     */
    protected function prepareQueryToExecute(Query $query)
    {
        $query->setFirstResult($this->firstResult + $query->getMaxResults() * $this->page);
    }
}
