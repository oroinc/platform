<?php

namespace Oro\Bundle\BatchBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query;

/**
 * Represents the iteration strategy for BufferedIdentityQueryResultIterator.
 */
interface IdentityIterationStrategyInterface
{
    /**
     * Initializes the query that should be used to load all identities.
     */
    public function initializeIdentityQuery(Query $query);

    /**
     * Initializes the query that should be used to load data by pages.
     */
    public function initializeDataQuery(Query $query);

    /**
     * Sets the list of identifiers for which the data should be loaded.
     */
    public function setDataQueryIdentifiers(Query $query, array $identifiers);
}
