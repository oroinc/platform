<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Provides common functionality for IMAP search query expression values.
 *
 * This base class encapsulates a search value (word phrase or nested expression) and its match type,
 * providing the foundation for building IMAP search criteria. Subclasses should implement specific
 * value types for different IMAP search keys.
 */
abstract class SearchQueryExprValueBase implements SearchQueryExprValueInterface
{
    /**
     * @param string|SearchQueryExpr $value The word phrase
     * @param int $match The match type. One of SearchQueryMatch::* values
     */
    public function __construct($value, $match)
    {
        $this->value = $value;
        $this->match = $match;
    }

    /**
     * A word phrase or instance of SearchQueryExpr class
     *
     * @var string|SearchQueryExpr
     */
    private $value;

    /**
     * A match type. One of SearchQueryMatch::* values
     *
     * @var int
     */
    private $match;

    /**
     * @return string|SearchQueryExpr
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string|SearchQueryExpr $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     * @see SearchQueryMatch
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * @param int $match One of SearchQueryMatch::* values
     * @see SearchQueryMatch
     */
    public function setMatch($match)
    {
        $this->match = $match;
    }
}
