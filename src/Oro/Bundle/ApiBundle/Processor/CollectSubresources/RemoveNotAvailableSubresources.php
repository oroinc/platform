<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes not accessible sub-resources and sub-resources if all their actions are excluded.
 */
class RemoveNotAvailableSubresources implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectSubresourcesContext $context */

        $accessibleResources = \array_fill_keys($context->getAccessibleResources(), true);
        $subresources = $context->getResult();

        $resources = $context->getResources();
        foreach ($resources as $entityClass => $resource) {
            $entitySubresources = $subresources->get($entityClass);
            if (null !== $entitySubresources) {
                $this->removeNotAvailableSubresources($entitySubresources, $accessibleResources);
            }
        }
    }

    /**
     * @param ApiResourceSubresources $entitySubresources
     * @param array                   $accessibleResources
     */
    private function removeNotAvailableSubresources(
        ApiResourceSubresources $entitySubresources,
        array $accessibleResources
    ): void {
        $numberOfSubresourceActions = \count(SubresourceUtil::SUBRESOURCE_ACTIONS);
        $subresources = $entitySubresources->getSubresources();
        foreach ($subresources as $associationName => $subresource) {
            if (SubresourceUtil::isAccessibleSubresource($subresource, $accessibleResources)) {
                if (\count($subresource->getExcludedActions()) === $numberOfSubresourceActions) {
                    // remove sub-resource if all its actions are excluded
                    $entitySubresources->removeSubresource($associationName);
                }
            } else {
                // remove not accessible sub-resource
                $entitySubresources->removeSubresource($associationName);
            }
        }
    }
}
