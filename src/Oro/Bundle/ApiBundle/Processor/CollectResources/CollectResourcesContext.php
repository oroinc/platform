<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

/**
 * The execution context for processors for "collect_resources" action.
 * @method ApiResourceCollection|ApiResource[] getResult()
 */
class CollectResourcesContext extends ApiContext
{
    /** @var string[] */
    protected $accessibleResources = [];

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        $this->setResult(new ApiResourceCollection());
    }

    /**
     * Gets a list of resources accessible through Data API.
     *
     * @return string[] The list of class names
     */
    public function getAccessibleResources()
    {
        return $this->accessibleResources;
    }

    /**
     * Sets a list of resources accessible through Data API.
     *
     * @param string[] $classNames
     */
    public function setAccessibleResources(array $classNames)
    {
        $this->accessibleResources = $classNames;
    }
}
