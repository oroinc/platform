<?php

namespace Oro\Bundle\SearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

/**
 * Defines the contract for creating search query instances.
 *
 * This interface specifies the method for creating {@see SearchQueryInterface} instances
 * from configuration arrays. Implementations are responsible for instantiating
 * appropriate query objects that can be executed against the search engine.
 */
interface QueryFactoryInterface
{
    /**
     * Creating the Query wrapper object in the given
     * Datasource context.
     *
     * @param array $config
     * @return SearchQueryInterface
     */
    public function create(array $config = []);
}
