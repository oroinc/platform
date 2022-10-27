<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds a restriction by the primary entity identifier to the ORM QueryBuilder
 * that is used to load data for "tags" association of a taggable entity.
 */
class AddParentEntityIdToTagsSubresourceQuery implements ProcessorInterface
{
    private TaggableHelper $taggableHelper;

    public function __construct(TaggableHelper $taggableHelper)
    {
        $this->taggableHelper = $taggableHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if (!$this->taggableHelper->isTaggable($context->getParentClassName())) {
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            return;
        }

        $query
            ->innerJoin(Tagging::class, 'tagging', Join::WITH, sprintf(
                'tagging.tag = %s',
                QueryBuilderUtil::getSingleRootAlias($query)
            ))
            ->andWhere(sprintf(
                'tagging.entityName = :class AND tagging.recordId = :%s',
                AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME
            ))
            ->setParameter('class', $context->getParentClassName())
            ->setParameter(AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME, $context->getParentId());
    }
}
