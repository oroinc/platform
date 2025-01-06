<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates whether an access to the requested action for the entity
 * specified in the "class" property of the context is granted.
 */
class ValidateActionAvailability implements ProcessorInterface
{
    private ResourcesProvider $resourcesProvider;

    public function __construct(ResourcesProvider $resourcesProvider)
    {
        $this->resourcesProvider = $resourcesProvider;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $excludeActions = $this->resourcesProvider->getResourceExcludeActions(
            $context->getClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
        if (\in_array($context->getAction(), $excludeActions, true)) {
            throw new ActionNotAllowedException(
                $context->isMainRequest()
                    ? 'The action is not allowed.'
                    : \sprintf('The "%s" action is not allowed.', $context->getAction())
            );
        }
    }
}
