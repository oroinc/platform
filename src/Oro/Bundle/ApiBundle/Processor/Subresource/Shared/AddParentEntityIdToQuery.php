<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

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

        if (null === QueryBuilderUtil::getSingleRootAlias($query, false)) {
            // only queries with one root entity is supported
            return;
        }

        $parentConfig = $context->getParentConfig();
        $path = ConfigUtil::explodePropertyPath($this->getAssociationName($context));
        $pathLength = count($path);
        $parentJoinAlias = 'e';
        for ($i = 1; $i <= $pathLength; $i++) {
            $fieldName = $path[$pathLength - $i];
            $joinAlias = sprintf('parent_entity%d', $i);
            $parentPath = array_slice($path, 0, -$i);
            if (empty($parentPath)) {
                $parentClassName = $context->getParentClassName();
                if (!$this->doctrineHelper->isManageableEntityClass($parentClassName)) {
                    $parentResourceClass = $parentConfig->getParentResourceClass();
                    if ($parentResourceClass && $this->doctrineHelper->isManageableEntityClass($parentResourceClass)) {
                        $parentClassName = $parentResourceClass;
                    }
                }
                $isCollection = $context->isCollection();
            } else {
                $parentFieldConfig = $parentConfig->findFieldByPath($parentPath, true);
                $parentClassName = $parentFieldConfig->getTargetClass();
                $isCollection = $parentFieldConfig->getTargetEntity()
                    ->findField($fieldName, true)
                    ->isCollectionValuedAssociation();
            }
            $this->addJoinToParentEntity(
                $query,
                $parentClassName,
                $fieldName,
                $isCollection,
                $joinAlias,
                $parentJoinAlias
            );
            $parentJoinAlias = $joinAlias;
        }

        $parentId = $context->getParentId();
        $parentIdFieldNames = $parentConfig->getIdentifierFieldNames();
        if (!is_array($parentId) && count($parentIdFieldNames) === 1) {
            $query
                ->andWhere(QueryBuilderUtil::sprintf(
                    '%s.%s = :parent_entity_id',
                    $parentJoinAlias,
                    $parentConfig->getField($parentIdFieldNames[0])->getPropertyPath($parentIdFieldNames[0])
                ))
                ->setParameter('parent_entity_id', $parentId);
        } else {
            $i = 0;
            foreach ($parentIdFieldNames as $fieldName) {
                $i++;
                $parameterName = sprintf('parent_entity_id%d', $i);
                $query
                    ->andWhere(QueryBuilderUtil::sprintf(
                        '%s.%s = :%s',
                        $parentJoinAlias,
                        $parentConfig->getField($fieldName)->getPropertyPath($fieldName),
                        $parameterName
                    ))
                    ->setParameter($parameterName, $parentId[$fieldName]);
            }
        }
    }

    /**
     * @param QueryBuilder $query
     * @param string       $parentClassName
     * @param string       $associationName
     * @param bool         $isCollection
     * @param string       $joinAlias
     * @param string|null  $parentJoinAlias
     */
    protected function addJoinToParentEntity(
        QueryBuilder $query,
        $parentClassName,
        $associationName,
        $isCollection,
        $joinAlias,
        $parentJoinAlias = null
    ) {
        $joinFieldName = $this->getJoinFieldName($parentClassName, $associationName);
        if ($joinFieldName) {
            // bidirectional association
            $query->innerJoin('e.' . $joinFieldName, $joinAlias);
        } else {
            if (!$parentJoinAlias) {
                $parentJoinAlias = QueryBuilderUtil::getSingleRootAlias($query);
            }
            if ($isCollection) {
                // unidirectional "to-many" association
                $query->innerJoin(
                    $parentClassName,
                    $joinAlias,
                    Join::WITH,
                    QueryBuilderUtil::sprintf('%s MEMBER OF %s.%s', $parentJoinAlias, $joinAlias, $associationName)
                );
            } else {
                // unidirectional "to-one" association
                $query->innerJoin(
                    $parentClassName,
                    $joinAlias,
                    Join::WITH,
                    QueryBuilderUtil::sprintf('%s.%s = %s', $joinAlias, $associationName, $parentJoinAlias)
                );
            }
        }
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
