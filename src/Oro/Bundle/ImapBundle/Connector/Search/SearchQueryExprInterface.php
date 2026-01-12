<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Defines the contract for IMAP search query expression components.
 *
 * This is the base interface for all search query expression elements used in building
 * IMAP SEARCH command queries. Implementations represent different types of search
 * expression components such as operators (AND, OR, NOT), values, and named items.
 */
interface SearchQueryExprInterface
{
}
