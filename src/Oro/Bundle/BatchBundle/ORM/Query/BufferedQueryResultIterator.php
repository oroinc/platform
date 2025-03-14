<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\Query;

/**
 * Iterates results of Query using buffer, allows to iterate large query
 * results without risk of getting out of memory error.
 *
 * This class has problems when iterating through changing dataset.
 * Use BufferedIdentityQueryResultIterator instead.
 */
class BufferedQueryResultIterator extends AbstractBufferedQueryResultIterator
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
    protected $requestedBufferSize = self::DEFAULT_BUFFER_SIZE;

    /**
     * Total count of records that should be iterated
     *
     * @var int
     */
    protected $totalCount;

    /**
     * Index of current page
     *
     * @var int
     */
    protected $page = -1;

    /**
     * Offset of current record in current page
     *
     * @var int
     */
    protected $offset = -1;

    /**
     * A position of a current record within the current page
     *
     * @var int
     */
    protected $position = -1;

    /**
     * Rows that where loaded for current page
     *
     * @var array
     */
    protected $rows;

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
     * Walk through results in reverse order
     * Useful when selected records are being updated in between page load
     *
     * @var bool
     */
    protected $reverse = false;

    #[\Override]
    public function setBufferSize($bufferSize)
    {
        parent::setBufferSize($bufferSize);
        $this->requestedBufferSize = (int)$bufferSize;

        return $this;
    }

    /**
     * Sets iteration order
     *
     * @param bool $reverse Determines the iteration order
     *
     * @return $this
     */
    public function setReverse($reverse)
    {
        $this->assertQueryWasNotExecuted('reverse mode');
        $this->reverse = $reverse;

        return $this;
    }

    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    #[\Override]
    public function next(): void
    {
        $this->offset++;

        if (!isset($this->rows[$this->offset]) && !$this->loadNextPage()) {
            $this->current = null;
        } else {
            $this->current = $this->rows[$this->offset];
            $this->position = $this->offset + $this->getQuery()->getMaxResults() * $this->page;
        }
    }

    #[\Override]
    public function rewind(): void
    {
        // reset total count only if at least one item was loaded by this iterator
        // for example if we call count method and then start iteration the total count must be calculated once
        if (null !== $this->totalCount && $this->offset !== -1) {
            $this->totalCount = null;
        }

        $this->offset = -1;
        $this->page = -1;
        $this->position = -1;
        $this->current = null;

        $this->next();
    }

    #[\Override]
    public function count(): int
    {
        if (null === $this->totalCount) {
            $query = $this->cloneQuery($this->getQuery());
            // restore original max results
            $query->setMaxResults($this->maxResults);

            $this->totalCount = QueryCountCalculator::calculateCount($query, $this->useCountWalker);
        }

        return $this->totalCount;
    }

    #[\Override]
    protected function initializeQuery(Query $query)
    {
        $this->maxResults = $query->getMaxResults();
        if (!$this->maxResults || $this->requestedBufferSize < $this->maxResults) {
            $query->setMaxResults($this->requestedBufferSize);
        }
        $this->firstResult = (int)$query->getFirstResult();
    }

    /**
     * Attempts to load next page
     *
     * @return bool If page loaded successfully
     */
    protected function loadNextPage()
    {
        if ($this->pageCallback && $this->page !== -1) {
            call_user_func($this->pageCallback);
        }

        $query = $this->getQuery();

        if (!$this->calculateNextPage($query)) {
            return false;
        }

        $this->prepareQueryToExecute($query);

        $this->rows = $query->execute();

        if ($this->pageLoadedCallback) {
            $this->rows = call_user_func($this->pageLoadedCallback, $this->rows);
        }

        return count($this->rows) > 0;
    }

    protected function calculateNextPage(Query $query): bool
    {
        $totalPages = ceil($this->count() / $query->getMaxResults());
        if ($this->reverse) {
            if ($this->page === -1) {
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

        return true;
    }

    /**
     * Makes final preparation of a query object before its execute method will be called.
     */
    protected function prepareQueryToExecute(Query $query)
    {
        $query->setFirstResult($this->firstResult + $query->getMaxResults() * $this->page);
    }
}
