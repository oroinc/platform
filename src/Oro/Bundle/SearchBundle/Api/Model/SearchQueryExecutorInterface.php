<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

/**
 * Represents a service that is used to wrap execution of search queries in API
 * to catch the search driver specific exceptions
 * and raise {@see InvalidSearchQueryException} when the search query is not valid.
 */
interface SearchQueryExecutorInterface
{
    public function execute(callable $callback): mixed;
}
