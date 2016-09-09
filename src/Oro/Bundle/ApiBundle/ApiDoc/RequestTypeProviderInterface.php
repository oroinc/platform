<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;

interface RequestTypeProviderInterface
{
    /**
     * @return RequestType|null
     */
    public function getRequestType();
}
