<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes sub-resources if all their actions are excluded.
 */
class RemoveNotAvailableSubresources implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectSubresourcesContext $context */

        $resources = $context->getResources();
        $subresources = $context->getResult();
        $numberOfSubresourceActions = \count(SubresourceUtil::SUBRESOURCE_ACTIONS);
        foreach ($resources as $entityClass => $resource) {
            $entitySubresources = $subresources->get($entityClass);
            if (null !== $entitySubresources) {
                $this->removeNotAvailableSubresources($entitySubresources, $numberOfSubresourceActions);
            }
        }
    }

    private function removeNotAvailableSubresources(
        ApiResourceSubresources $entitySubresources,
        int $numberOfSubresourceActions
    ): void {
        $subresources = $entitySubresources->getSubresources();
        foreach ($subresources as $associationName => $subresource) {
            if (\count($subresource->getExcludedActions()) === $numberOfSubresourceActions) {
                // remove sub-resource if all its actions are excluded
                $entitySubresources->removeSubresource($associationName);
            }
        }
    }
}
