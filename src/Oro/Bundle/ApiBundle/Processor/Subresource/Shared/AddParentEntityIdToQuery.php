<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

/**
 * Adds restriction by the primary entity identifier to the ORM QueryBuilder
 * that is used to load data.
 */
class AddParentEntityIdToQuery implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $query = $context->getQuery();
        if (!$query || !$query instanceof QueryBuilder) {
            // a query does not exist or it is not supported type
            return;
        }
        $rootAlias = QueryUtils::getSingleRootAlias($query, false);
        if (!$rootAlias) {
            // only queries with one root entity is supported
            return;
        }
        $associationName = $this->getAssociationName($context);
        if (!$associationName) {
            // it is strange, but the association name cannot be recognized
            return;
        }

        if ($context->isCollection()) {
            $query->innerJoin(
                $context->getParentClassName(),
                'parent_entity',
                Join::WITH,
                sprintf('%s MEMBER OF parent_entity.%s', $rootAlias, $associationName)
            );
        } else {
            $query->innerJoin(
                $context->getParentClassName(),
                'parent_entity',
                Join::WITH,
                sprintf('parent_entity.%s = %s', $associationName, $rootAlias)
            );
        }
        $query
            ->andWhere('parent_entity = :parent_entity_id')
            ->setParameter('parent_entity_id', $context->getParentId());
    }

    /**
     * @param SubresourceContext $context
     *
     * @return string|null
     */
    protected function getAssociationName(SubresourceContext $context)
    {
        $associationName = $context->getAssociationName();
        $associationField = $context->getParentConfig()->getField($associationName);
        if (!$associationField) {
            // it is strange, but the parent entity does not have the requested association
            return null;
        }

        return $associationField->getPropertyPath() ?: $associationName;
    }
}
