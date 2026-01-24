<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Represents a logical operator in an IMAP search query expression.
 *
 * This class encapsulates logical operators used in IMAP SEARCH command queries,
 * such as AND, OR, NOT, and parentheses for grouping expressions. Each operator
 * has a name that identifies its type and function within the search expression.
 */
class SearchQueryExprOperator implements SearchQueryExprInterface
{
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Can be one of 'AND', 'OR', 'NOT', '(', ')'
     *
     * @var
     */
    private $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
