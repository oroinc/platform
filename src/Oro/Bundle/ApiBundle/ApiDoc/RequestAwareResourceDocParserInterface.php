<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * This interface should be implemented by API resource documentation parsers that depend on a request type.
 */
interface RequestAwareResourceDocParserInterface
{
    /**
     * Sets the request type.
     */
    public function setRequestType(RequestType $requestType): void;
}
