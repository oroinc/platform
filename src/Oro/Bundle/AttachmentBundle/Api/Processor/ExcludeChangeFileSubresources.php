<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectSubresources\SubresourceUtil;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes the following actions for file associations:
 * * update_relationship
 * * add_relationship
 * * delete_relationship
 */
class ExcludeChangeFileSubresources implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CollectSubresourcesContext $context */

        $subresources = $context->getResult();
        $resources = $context->getResources();
        foreach ($resources as $entityClass => $resource) {
            if (!SubresourceUtil::isSubresourcesEnabled($resource)) {
                continue;
            }

            $entitySubresources = $subresources->get($entityClass);
            if (null === $entitySubresources) {
                continue;
            }
            $subresourcesForEntity = $entitySubresources->getSubresources();
            if (!$subresourcesForEntity) {
                continue;
            }

            foreach ($subresourcesForEntity as $subresource) {
                if (File::class === $subresource->getTargetClassName()) {
                    $subresource->addExcludedAction(ApiAction::UPDATE_RELATIONSHIP);
                    if ($subresource->isCollection()) {
                        $subresource->addExcludedAction(ApiAction::ADD_RELATIONSHIP);
                        $subresource->addExcludedAction(ApiAction::DELETE_RELATIONSHIP);
                    }
                }
            }
        }
    }
}
