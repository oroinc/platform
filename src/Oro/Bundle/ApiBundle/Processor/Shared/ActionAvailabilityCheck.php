<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;

/**
 * Validates whether the requested action of entity specified
 * in the "class" property of the Context is not excluded.
 */
class ActionAvailabilityCheck implements ProcessorInterface
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
        /** @var Context $context */

        $excludeActions = $this->resourcesProvider->getResourceExcludeActions(
            $context->getClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
        if (in_array($context->getAction(), $excludeActions, true)) {
            throw new ActionNotAllowedException();
        }
    }
}
