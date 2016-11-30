<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * This interface should be implemented by filters that depends on a request type.
 */
interface RequestAwareFilterInterface
{
    /**
     * Sets the request type.
     *
     * @param RequestType $requestType
     */
    public function setRequestType(RequestType $requestType);
}
