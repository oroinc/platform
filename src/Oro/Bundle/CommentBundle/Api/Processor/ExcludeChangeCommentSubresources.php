<?php

namespace Oro\Bundle\CommentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectSubresources\SubresourceUtil;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\CommentBundle\Api\CommentAssociationProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes the following actions for associations with the comment entity:
 * * update_relationship
 * * add_relationship
 * * delete_relationship
 */
class ExcludeChangeCommentSubresources implements ProcessorInterface
{
    private const COMMENTS_ASSOCIATION_NAME = 'comments';

    private CommentAssociationProvider $commentAssociationProvider;

    public function __construct(CommentAssociationProvider $commentAssociationProvider)
    {
        $this->commentAssociationProvider = $commentAssociationProvider;
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
            $commentAssociationName = $this->commentAssociationProvider->getCommentAssociationName(
                $entityClass,
                $version,
                $requestType
            );
            if (!$commentAssociationName) {
                continue;
            }

            $entitySubresources = $subresources->get($entityClass);
            if (null === $entitySubresources) {
                continue;
            }
            $commentsSubresource = $entitySubresources->getSubresource(self::COMMENTS_ASSOCIATION_NAME);
            if (null === $commentsSubresource) {
                continue;
            }
            $commentsSubresource->addExcludedAction(ApiAction::UPDATE_RELATIONSHIP);
            $commentsSubresource->addExcludedAction(ApiAction::ADD_RELATIONSHIP);
            $commentsSubresource->addExcludedAction(ApiAction::DELETE_RELATIONSHIP);
        }
    }
}
