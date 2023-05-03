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
    private array $accessibleResources = [];
    /** @var string[] */
    private array $accessibleAsAssociationResources = [];

    /**
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->setResult(new ApiResourceCollection());
    }

    /**
     * Gets a list of resources accessible through API.
     *
     * @return string[] The list of class names
     */
    public function getAccessibleResources(): array
    {
        return $this->accessibleResources;
    }

    /**
     * Sets a list of resources accessible through API.
     *
     * @param string[] $classNames
     */
    public function setAccessibleResources(array $classNames): void
    {
        $this->accessibleResources = $classNames;
    }

    /**
     * Gets a list of resources accessible as an association in API.
     *
     * @return string[] The list of class names
     */
    public function getAccessibleAsAssociationResources(): array
    {
        return $this->accessibleAsAssociationResources;
    }

    /**
     * Sets a list of resources accessible as an association in API.
     *
     * @param string[] $classNames
     */
    public function setAccessibleAsAssociationResources(array $classNames): void
    {
        $this->accessibleAsAssociationResources = $classNames;
    }
}
