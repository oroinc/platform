<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Defines the contract for value-based search query expression components.
 *
 * This interface marks search query expression elements that represent values or value-containing items.
 * It extends SearchQueryExprInterface and serves as a base for expressions that carry search values,
 * distinguishing them from pure operators in the search expression hierarchy.
 */
interface SearchQueryExprValueInterface extends SearchQueryExprInterface
{
}
