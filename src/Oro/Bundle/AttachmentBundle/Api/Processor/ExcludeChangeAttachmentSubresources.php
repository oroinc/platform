<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectSubresources\SubresourceUtil;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\AttachmentBundle\Api\AttachmentAssociationProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes the following actions for associations with the attachment entity:
 * * update_relationship
 * * add_relationship
 * * delete_relationship
 */
class ExcludeChangeAttachmentSubresources implements ProcessorInterface
{
    private const ATTACHMENTS_ASSOCIATION_NAME = 'attachments';

    private AttachmentAssociationProvider $attachmentAssociationProvider;

    public function __construct(AttachmentAssociationProvider $attachmentAssociationProvider)
    {
        $this->attachmentAssociationProvider = $attachmentAssociationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectSubresourcesContext $context */

        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $subresources = $context->getResult();
        $resources = $context->getResources();
        foreach ($resources as $entityClass => $resource) {
            if (!SubresourceUtil::isSubresourcesEnabled($resource)) {
                continue;
            }
            $attachmentAssociationName = $this->attachmentAssociationProvider->getAttachmentAssociationName(
                $entityClass,
                $version,
                $requestType
            );
            if (!$attachmentAssociationName) {
                continue;
            }

            $entitySubresources = $subresources->get($entityClass);
            if (null === $entitySubresources) {
                continue;
            }
            $attachmentsSubresource = $entitySubresources->getSubresource(self::ATTACHMENTS_ASSOCIATION_NAME);
            if (null === $attachmentsSubresource) {
                continue;
            }
            $attachmentsSubresource->addExcludedAction(ApiAction::UPDATE_RELATIONSHIP);
            $attachmentsSubresource->addExcludedAction(ApiAction::ADD_RELATIONSHIP);
            $attachmentsSubresource->addExcludedAction(ApiAction::DELETE_RELATIONSHIP);
        }
    }
}
