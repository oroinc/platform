<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds a list of resources accessible through API.
 */
class LoadAccessibleResources implements ProcessorInterface
{
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;

    public function __construct(EntityOverrideProviderRegistry $entityOverrideProviderRegistry)
    {
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */

        $accessibleResources = $context->getAccessibleResources();
        if ($accessibleResources) {
            // accessible resources are already built
            return;
        }

        $accessibleAsAssociationResources = $context->getAccessibleAsAssociationResources();
        $entityOverrideProvider = $this->entityOverrideProviderRegistry
            ->getEntityOverrideProvider($context->getRequestType());
        $resources = $context->getResult();
        foreach ($resources as $resource) {
            $excludedActions = $resource->getExcludedActions();
            if (!\in_array(ApiAction::GET, $excludedActions, true)) {
                $entityClass = $resource->getEntityClass();
                if (null === $entityOverrideProvider->getSubstituteEntityClass($entityClass)) {
                    $accessibleResources[] = $entityClass;
                    $accessibleAsAssociationResources[] = $entityClass;
                }
            } elseif (!\in_array(ApiAction::GET_LIST, $excludedActions, true)) {
                $entityClass = $resource->getEntityClass();
                if (null === $entityOverrideProvider->getSubstituteEntityClass($entityClass)) {
                    $accessibleResources[] = $entityClass;
                }
            }
        }
        $context->setAccessibleResources($accessibleResources);
        $context->setAccessibleAsAssociationResources($accessibleAsAssociationResources);
    }
}
