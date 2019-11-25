<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractErrorCompleter;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * The error completer for plain REST API response.
 */
class ErrorCompleter extends AbstractErrorCompleter
{
    /**
     * {@inheritdoc}
     */
    public function complete(Error $error, RequestType $requestType, EntityMetadata $metadata = null): void
    {
        $this->completeStatusCode($error);
        $this->completeCode($error);
        $this->completeTitle($error);
        $this->completeDetail($error);
    }

    /**
     * {@inheritdoc}
     */
    public function fixIncludedEntityPath(
        string $entityPath,
        Error $error,
        RequestType $requestType,
        EntityMetadata $metadata = null
    ): void {
    }
}
