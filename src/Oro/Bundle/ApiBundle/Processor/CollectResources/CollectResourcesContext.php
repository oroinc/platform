<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

/**
 * @method ApiResourceCollection|ApiResource[] getResult()
 */
class CollectResourcesContext extends ApiContext
{
    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setResult(new ApiResourceCollection());
    }
}
