<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates whether an access to the requested action of entity specified
 * in the "parentClass" property of the context is granted.
 */
class ParentActionAvailabilityCheck implements ProcessorInterface
{
    /** @var ResourcesProvider */
    protected $resourcesProvider;

    /**
     * @param ResourcesProvider $resourcesProvider
     */
    public function __construct(ResourcesProvider $resourcesProvider)
    {
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $excludeActions = $this->resourcesProvider->getResourceExcludeActions(
            $context->getParentClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
        if (in_array($context->getAction(), $excludeActions, true)) {
            throw new ActionNotAllowedException();
        }
    }
}
