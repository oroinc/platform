<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Provides an interface for classes that complete properties of the Error objects
 * for different kind of API requests.
 */
interface ErrorCompleterInterface
{
    /**
     * Completes all properties of the given Error object.
     */
    public function complete(Error $error, RequestType $requestType, EntityMetadata $metadata = null): void;

    /**
     * Adds the given entity path to the source of the given Error object.
     */
    public function fixIncludedEntityPath(
        string $entityPath,
        Error $error,
        RequestType $requestType,
        EntityMetadata $metadata = null
    ): void;
}
