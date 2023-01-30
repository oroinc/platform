<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\TreeListener;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds restriction by the primary entity identifier
 * for "path" subresource for an entity that is a node of a tree.
 */
class AddParentEntityIdToTreeNodePathQuery implements ProcessorInterface
{
    private TreeListener $treeListener;
    private DoctrineHelper $doctrineHelper;
    private ?string $sourceEntityClass = null;

    public function __construct(TreeListener $treeListener, DoctrineHelper $doctrineHelper)
    {
        $this->treeListener = $treeListener;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function setSourceEntityClass(string $sourceEntityClass): void
    {
        $this->sourceEntityClass = $sourceEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $this->sourceEntityClass ?? $context->getClassName(),
            $context->getConfig()
        );
        $treeConfig = $this->treeListener->getConfiguration(
            $this->doctrineHelper->getEntityManagerForClass($entityClass),
            $entityClass
        );
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($query, false);

        $joinExpr = QueryBuilderUtil::sprintf(
            '%1$s.%2$s > parent.%2$s AND %1$s.%3$s < parent.%3$s',
            $rootAlias,
            $treeConfig['right'],
            $treeConfig['left']
        );
        if (isset($treeConfig['root'])) {
            $joinExpr .= QueryBuilderUtil::sprintf(
                ' AND %1$s.%2$s = parent.%2$s',
                $rootAlias,
                $treeConfig['root']
            );
        }

        $query
            ->innerJoin($entityClass, 'parent', Join::WITH, $joinExpr)
            ->where('parent = :' . AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME)
            ->setParameter(AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME, $context->getParentId())
            ->orderBy(QueryBuilderUtil::getField($rootAlias, $treeConfig['left']));
    }
}
