<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validates whether a feature that related to a parent API resource is enabled
 * and validates whether an association is enabled.
 */
class ValidateParentEntityTypeFeature implements ProcessorInterface
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
        /** @var SubresourceContext $context */

        if (!$this->resourcesProvider->isResourceEnabled(
            $context->getParentClassName(),
            $context->getAction(),
            $context->getVersion(),
            $context->getRequestType()
        )) {
            throw new NotFoundHttpException();
        }

        $associationName = $context->getAssociationName();
        $parentMetadata = $context->getParentMetadata();
        if (null !== $parentMetadata && !$parentMetadata->hasAssociation($associationName)) {
            $parentConfig = $context->getParentConfig();
            if (null !== $parentConfig && $this->isExcludedAssociation($parentConfig, $associationName)) {
                throw new NotFoundHttpException();
            }
        }
    }

    private function isExcludedAssociation(EntityDefinitionConfig $parentConfig, string $associationName): bool
    {
        $associationConfig = $parentConfig->getField($associationName);

        return null !== $associationConfig && $associationConfig->isExcluded();
    }
}
