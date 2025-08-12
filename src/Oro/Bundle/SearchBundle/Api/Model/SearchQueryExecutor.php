<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

use Doctrine\DBAL\Exception\DriverException;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;

/**
 * This service is used to wrap execution of search queries in API
 * to catch the search driver specific exceptions
 * and raise {@see InvalidSearchQueryException} when the search query is not valid.
 */
class SearchQueryExecutor implements SearchQueryExecutorInterface
{
    public function execute(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (DriverException $e) {
            throw new InvalidSearchQueryException('Invalid search query.', $e->getCode(), $e);
        }
    }
}
