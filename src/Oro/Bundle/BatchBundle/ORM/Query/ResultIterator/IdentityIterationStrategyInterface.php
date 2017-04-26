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
     *
     * @param Query $query
     */
    public function initializeIdentityQuery(Query $query);

    /**
     * Initializes the query that should be used to load data by pages.
     *
     * @param Query $query
     */
    public function initializeDataQuery(Query $query);

    /**
     * Sets the list of identifiers for which the data should be loaded.
     *
     * @param Query $query
     * @param array $identifiers
     */
    public function setDataQueryIdentifiers(Query $query, array $identifiers);
}
