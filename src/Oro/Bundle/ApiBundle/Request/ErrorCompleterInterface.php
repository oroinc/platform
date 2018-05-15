<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Provides an interface for classes that complete properties of the Error objects
 * for different kind of Data API requests.
 */
interface ErrorCompleterInterface
{
    /**
     * Completes all properties of a given Error object.
     *
     * @param Error               $error
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     */
    public function complete(Error $error, RequestType $requestType, EntityMetadata $metadata = null);
}
