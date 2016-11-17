<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Adds restriction by the primary entity identifier to the ORM QueryBuilder
 * that is used to load data.
 */
class AddParentEntityIdToQuery implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

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
        $parentClassName = $context->getParentClassName();
        $joinFieldName = $this->getJoinFieldName($parentClassName, $associationName);
        if ($joinFieldName) {
            // bidirectional association
            $query->innerJoin('e.' . $joinFieldName, 'parent_entity');
        } elseif ($context->isCollection()) {
            // unidirectional "to-many" association
            $query->innerJoin(
                $parentClassName,
                'parent_entity',
                Join::WITH,
                sprintf('%s MEMBER OF parent_entity.%s', $rootAlias, $associationName)
            );
        } else {
            // unidirectional "to-one" association
            $query->innerJoin(
                $parentClassName,
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
        $propertyPath = $context->getParentConfig()
            ->getField($associationName)
            ->getPropertyPath();

        return $propertyPath ?: $associationName;
    }

    /**
     * @param string $parentClassName
     * @param string $associationName
     *
     * @return string|null
     */
    protected function getJoinFieldName($parentClassName, $associationName)
    {
        $parentMetadata = $this->doctrineHelper->getEntityMetadataForClass($parentClassName);
        if (!$parentMetadata->hasAssociation($associationName)) {
            return null;
        }

        $associationMapping = $parentMetadata->getAssociationMapping($associationName);

        return $associationMapping['isOwningSide']
            ? $associationMapping['inversedBy']
            : $associationMapping['mappedBy'];
    }
}
