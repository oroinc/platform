<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

use Closure;

/**
 * Provides common functionality for building IMAP search queries.
 *
 * This base class implements a fluent interface for constructing IMAP search queries with support
 * for logical operators (AND, OR, NOT) and nested expressions.
 * Subclasses should extend this to provide specific search criteria methods for different IMAP search keys.
 */
abstract class AbstractSearchQueryBuilder
{
    protected $query;

    /**
     * Constructor.
     */
    public function __construct(SearchQuery $query)
    {
        $this->query = $query;
    }

    /**
     * Adds AND operator.
     *
     * @param Closure|null $callback
     * @return $this
     */
    public function andOperator(?Closure $callback = null)
    {
        $this->query->andOperator();
        if ($callback instanceof Closure) {
            $this->processCallback($callback);
        }

        return $this;
    }

    /**
     * Adds OR operator.
     *
     * @param Closure|null $callback
     * @return $this
     */
    public function orOperator(?Closure $callback = null)
    {
        $this->query->orOperator();
        if ($callback instanceof Closure) {
            $this->processCallback($callback);
        }

        return $this;
    }

    /**
     * Adds OR operator.
     *
     * @param Closure|null $callback
     * @return $this
     */
    public function notOperator(?Closure $callback = null)
    {
        $this->query->notOperator();
        if ($callback instanceof Closure) {
            $this->processCallback($callback);
        }

        return $this;
    }

    /**
     * Adds open parenthesis '('.
     *
     * @return $this
     */
    public function openParenthesis()
    {
        $this->query->openParenthesis();

        return $this;
    }

    /**
     * Adds close parenthesis ')'.
     *
     * @return $this
     */
    public function closeParenthesis()
    {
        $this->query->closeParenthesis();

        return $this;
    }

    /**
     * Returns the built SearchQuery object.
     *
     * @return SearchQuery
     */
    public function get()
    {
        return $this->query;
    }

    private function processCallback(Closure $callback)
    {
        $this->query->openParenthesis();
        call_user_func($callback, $this);
        $this->query->closeParenthesis();
    }
}
