<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Represents a named search criterion in an IMAP search query expression.
 *
 * This class encapsulates a search item that targets a specific message property (e.g., FROM, TO, SUBJECT)
 * with a particular value and match type. It combines a property name with a search value, allowing
 * developers to build complex IMAP search queries that filter messages based on specific criteria.
 */
class SearchQueryExprItem extends SearchQueryExprValueBase implements
    SearchQueryExprNamedItemInterface,
    SearchQueryExprValueInterface,
    SearchQueryExprInterface
{
    /**
     * @param string $name The property name
     * @param string|SearchQueryExpr $value The word phrase
     * @param int $match The match type. One of SearchQueryMatch::* values
     */
    public function __construct($name, $value, $match)
    {
        parent::__construct($value, $match);
        $this->name = $name;
    }

    /**
     * The name of a property
     *
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    #[\Override]
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    #[\Override]
    public function setName($name)
    {
        $this->name = $name;
    }
}
