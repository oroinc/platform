<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

/**
 * @method ApiResourceCollection getResult()
 */
class CollectResourcesContext extends ApiContext
{
    public function __construct()
    {
        parent::__construct();
        $this->setResult(new ApiResourceCollection());
    }
}
