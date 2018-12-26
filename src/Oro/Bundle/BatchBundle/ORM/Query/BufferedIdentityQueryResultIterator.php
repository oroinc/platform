<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierIterationStrategy;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentityIterationStrategyInterface;

/**
 * Iterates results of Query by pre-loaded record identifiers.
 * Allows to iterate large query results without risk of getting out of memory error.
 * Allows to iterate through changing dataset.
 * 1,5 million rows in a query will approximately require 80mb of memory to store identifiers.
 * Ensures first query result will be constant during pagination.
 *
 * Iterates through Root Entities
 * Buffer Size applied to Root Entities
 * OneToMany joins may lead to increasing number of actual fetched rows in a batch
 * Actual batch size may vary from page to page when joins used in a query
 *
 * In complex cases implement IdentityIterationStrategyInterface with custom iteration strategy and set it using
 * setIterationStrategy() method.
 * Or use BufferedQueryResultIterator
 */
class BufferedIdentityQueryResultIterator extends AbstractBufferedQueryResultIterator
{
    /**
     * Count of records that will be loaded on each page during iterations
     */
    const DEFAULT_BUFFER_SIZE = 200;

    /** @var IdentityIterationStrategyInterface */
    protected $iterationStrategy;

    /** @var array */
    private $identifiers;

    /**
     * Virtual Page size - number of Root Entities in a Page
     * Could be not equal to actual rows in a batch when joins used in a Query
     *
     * @var int
     */
    private $pageSize = self::DEFAULT_BUFFER_SIZE;

    /**
     * The maximum number of results the original query object was set to retrieve
     *
     * @var int
     */
    private $maxResults;

    /**
     * Total count of records that should be iterated
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
     * {@inheritDoc}
     *
     * $bufferSize applies to Root Entities,
     * OneToMany joins will result in increasing number of actual fetched rows in a batch
     */
    public function setBufferSize($bufferSize)
    {
        parent::setBufferSize($bufferSize);
        $this->pageSize = (int)$bufferSize;

        return $this;
    }

    /**
     * Sets the iteration strategy.
     * The default strategy is IdentifierIterationStrategy.
     *
     * @param IdentityIterationStrategyInterface $iterationStrategy
     *
     * @return $this
     */
    public function setIterationStrategy(IdentityIterationStrategyInterface $iterationStrategy)
    {
        $this->assertQueryWasNotExecuted('iteration strategy');

        $this->iterationStrategy = $iterationStrategy;

        return $this;
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
    public function next()
    {
        if (null === $this->identifiers) {
            $this->rewind();
            return;
        }

        $this->offset++;

        if (!isset($this->rows[$this->offset])) {
            $this->offset = 0;
            $this->loadNextPage();
        }

        if ($this->rows) {
            $this->current = $this->rows[$this->offset];
            $this->position = $this->offset + $this->pageSize * $this->page;
        } else {
            $this->current = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->offset = -1;
        $this->page = -1;
        $this->position = -1;
        $this->current = null;

        $this->ensureIdentifiersLoaded();
        $this->getIterationStrategy()->initializeDataQuery($this->getQuery());

        $this->next();
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        if ($this->totalCount === null) {
            if ($this->useCountWalker || $this->maxResults) {
                // if maxResults is set in a Query - Iterator will limit Root entities (distinct IDS)
                // Usage of Count walker duplicates this logic
                $this->ensureIdentifiersLoaded();
                $this->totalCount = count($this->identifiers);
            } else {
                $query = $this->cloneQuery($this->getQuery());
                // restore original max results
                $query->setMaxResults($this->maxResults);
                $this->totalCount = QueryCountCalculator::calculateCount($query, $this->useCountWalker);
            }
        }

        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeQuery(Query $query)
    {
        $this->maxResults = $query->getMaxResults();
        if ($this->maxResults && $this->maxResults < $this->pageSize) {
            $this->pageSize = $this->maxResults;
        }
    }

    /**
     * Loads next page
     */
    protected function loadNextPage()
    {
        if ($this->pageCallback && $this->page !== -1) {
            call_user_func($this->pageCallback);
        }

        $query = $this->getQuery();

        $this->page++;

        $this->getIterationStrategy()->setDataQueryIdentifiers(
            $query,
            array_slice(
                $this->identifiers,
                $this->pageSize * $this->page,
                $this->pageSize
            )
        );

        try {
            $this->rows = $query->execute();
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        if ($this->pageLoadedCallback) {
            $this->rows = call_user_func($this->pageLoadedCallback, $this->rows);
        }
    }

    /**
     * Makes sure the list of identifiers is loaded.
     */
    protected function ensureIdentifiersLoaded()
    {
        if (null !== $this->identifiers) {
            return;
        }

        $query = $this->cloneQuery($this->getQuery());
        $this->getIterationStrategy()->initializeIdentityQuery($query);

        try {
            $this->identifiers = $query->execute();
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * @return IdentityIterationStrategyInterface
     */
    protected function getIterationStrategy()
    {
        if (null === $this->iterationStrategy) {
            $this->iterationStrategy = new IdentifierIterationStrategy();
        }

        return $this->iterationStrategy;
    }

    /**
     * Handles Exception
     *
     * @param \Exception $e
     */
    private function handleException(\Exception $e)
    {
        throw new \LogicException(
            'Can not autodetect row identifier, set custom iteration strategy to handle complex query.',
            0,
            $e
        );
    }
}
