<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * An interface for classes that can provide the request type based on some context.
 */
interface RequestTypeProviderInterface
{
    /**
     * Returns the currently processed request type.
     *
     * @return RequestType|null The request type or NULL if it cannot be detected based on the current context.
     */
    public function getRequestType(): ?RequestType;
}
