<?php

namespace Oro\Bundle\SearchBundle\Api\Exception;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;

/**
 * This exception is thrown when a search query in not valid.
 * @see \Oro\Bundle\SearchBundle\Api\Model\SearchResult
 */
class InvalidSearchQueryException extends RuntimeException
{
}
