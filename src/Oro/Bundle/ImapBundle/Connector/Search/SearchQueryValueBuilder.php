<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Builds IMAP search query expressions for value-based searches.
 *
 * This builder extends the base search query builder to provide a fluent interface
 * for adding word phrases and values to be searched across all message properties.
 * It is typically used within closure callbacks to construct complex nested search expressions.
 */
class SearchQueryValueBuilder extends AbstractSearchQueryBuilder
{
    public function value($value)
    {
        $this->query->value($value);

        return $this;
    }
}
