<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds a list of resources accessible through Data API.
 */
class LoadAccessibleResources implements ProcessorInterface
{
    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /**
     * @param EntityOverrideProviderRegistry $entityOverrideProviderRegistry
     */
    public function __construct(EntityOverrideProviderRegistry $entityOverrideProviderRegistry)
    {
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectResourcesContext $context */

        $accessibleResources = $context->getAccessibleResources();
        if (!empty($accessibleResources)) {
            // the accessible resources are already built
            return;
        }

        $entityOverrideProvider = $this->entityOverrideProviderRegistry
            ->getEntityOverrideProvider($context->getRequestType());
        $resources = $context->getResult();
        foreach ($resources as $resource) {
            $entityClass = $resource->getEntityClass();
            if (!\in_array(ApiActions::GET, $resource->getExcludedActions(), true)
                && null === $entityOverrideProvider->getSubstituteEntityClass($entityClass)
            ) {
                $accessibleResources[] = $entityClass;
            }
        }
        $context->setAccessibleResources($accessibleResources);
    }
}
