<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to load data for "tags" association of a taggable entity.
 */
class BuildTagsSubresourceQuery implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private TaggableHelper $taggableHelper;

    public function __construct(DoctrineHelper $doctrineHelper, TaggableHelper $taggableHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taggableHelper = $taggableHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if (!$this->taggableHelper->isTaggable($context->getParentClassName())) {
            return;
        }

        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return;
        }

        $context->setQuery(
            $this->doctrineHelper->createQueryBuilder($entityClass, 'e')
                ->innerJoin(Tagging::class, 'tagging', Join::WITH, 'tagging.tag = e')
                ->andWhere(sprintf(
                    'tagging.entityName = :class AND tagging.recordId = :%s',
                    AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME
                ))
                ->setParameter('class', $context->getParentClassName())
                ->setParameter(AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME, $context->getParentId())
        );
    }
}
