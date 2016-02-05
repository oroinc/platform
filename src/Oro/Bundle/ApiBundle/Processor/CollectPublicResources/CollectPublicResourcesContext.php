<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectPublicResources;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\PublicResourceCollection;

/**
 * @method PublicResourceCollection getResult()
 */
class CollectPublicResourcesContext extends ApiContext
{
    public function __construct()
    {
        $this->setResult(new PublicResourceCollection());
    }
}
