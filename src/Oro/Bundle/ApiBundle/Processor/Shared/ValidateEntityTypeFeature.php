<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validates whether a feature that related to an API resource is enabled.
 */
class ValidateEntityTypeFeature implements ProcessorInterface
{
    private ResourcesProvider $resourcesProvider;

    public function __construct(ResourcesProvider $resourcesProvider)
    {
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$this->resourcesProvider->isResourceEnabled(
            $context->getClassName(),
            $context->getAction(),
            $context->getVersion(),
            $context->getRequestType()
        )) {
            throw new NotFoundHttpException();
        }
    }
}
