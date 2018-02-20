<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\Walker\PreciseOrderByWalker;

abstract class AbstractBufferedQueryResultIterator implements BufferedQueryResultIteratorInterface
{
    /** @var bool */
    protected $useCountWalker;

    /** @var callable|null */
    protected $pageCallback;

    /** @var callable|null */
    protected $pageLoadedCallback;

    /**
     * Defines the processing mode to be used during hydration / result set transformation
     * This is just recommended hydration mode because the real mode can be calculated automatically
     * in case when the requested hydration mode is not specified
     *
     * @var int|string
     */
    protected $requestedHydrationMode;

    /**
     * The source query
     *
     * @var Query|QueryBuilder
     */
    private $source;

    /**
     * Query to iterate
     *
     * @var Query
     */
    private $query;

    /**
     * Current record, populated from query result row
     *
     * @var mixed
     */
    protected $current;

    /**
     * @param Query|QueryBuilder $source
     * @param bool|null          $useCountWalker
     */
    public function __construct($source, $useCountWalker = null)
    {
        if ($source instanceof Query || $source instanceof QueryBuilder) {
            $this->source = $source;
            $this->useCountWalker = $useCountWalker;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The $source must be instance of "%s" or "%s", but "%s" given.',
                    Query::class,
                    QueryBuilder::class,
                    is_object($source) ? get_class($source) : gettype($source)
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function setBufferSize($bufferSize)
    {
        $this->assertQueryWasNotExecuted('buffer size');
        if ($bufferSize <= 0) {
            throw new \InvalidArgumentException('$bufferSize must be greater than 0');
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPageCallback(callable $callback = null)
    {
        $this->pageCallback = $callback;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPageLoadedCallback(callable $callback = null)
    {
        $this->pageLoadedCallback = $callback;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setHydrationMode($hydrationMode)
    {
        $this->assertQueryWasNotExecuted('hydration mode');

        $this->requestedHydrationMode = $hydrationMode;

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
    public function valid()
    {
        return null !== $this->current;
    }

    /**
     * @return Query
     */
    protected function getQuery()
    {
        if (null === $this->query) {
            $this->query = $this->source instanceof QueryBuilder
                ? $this->source->getQuery()
                : $this->cloneQuery($this->source);

            if (null !== $this->requestedHydrationMode) {
                $this->query->setHydrationMode($this->requestedHydrationMode);
            }

            // make sure the query has ORDER BY clause
            QueryUtil::addTreeWalker($this->query, PreciseOrderByWalker::class);

            $this->initializeQuery($this->query);
        }

        return $this->query;
    }

    /**
     * @param Query $query
     */
    abstract protected function initializeQuery(Query $query);

    /**
     * Makes full clone of the given query, including its parameters and hints
     *
     * @param Query $query
     *
     * @return Query
     */
    protected function cloneQuery(Query $query)
    {
        return QueryUtil::cloneQuery($query);
    }

    /**
     * Asserts that query was not executed, otherwise raise an exception
     *
     * @param string $optionLabel
     *
     * @throws \LogicException
     */
    protected function assertQueryWasNotExecuted($optionLabel)
    {
        if ($this->query) {
            throw new \LogicException(sprintf('Cannot set %s as the query was already executed.', $optionLabel));
        }
    }
}
