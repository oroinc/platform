<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds a restriction by the primary entity identifier to the ORM QueryBuilder
 * that is used to load data.
 * Also checks that this restriction was added to the ORM QueryBuilder for computed associations.
 */
class AddParentEntityIdToQuery implements ProcessorInterface
{
    public const PARENT_ENTITY_ID_QUERY_PARAM_NAME = 'parent_entity_id';

    private DoctrineHelper $doctrineHelper;
    private EntityIdHelper $entityIdHelper;

    public function __construct(DoctrineHelper $doctrineHelper, EntityIdHelper $entityIdHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // a query does not exist or it is not supported type
            return;
        }

        $rootAlias = QueryBuilderUtil::getSingleRootAlias($query, false);
        if (null === $rootAlias) {
            // only queries with one root entity is supported
            return;
        }

        $associationName = $this->getAssociationName($context);
        if (null === $associationName) {
            // skip sub-resources that do not associated with any field in the parent entity config
            return;
        }

        if (ConfigUtil::IGNORE_PROPERTY_PATH !== $associationName) {
            $parentJoinAlias = $this->joinParentEntity(
                $query,
                $rootAlias,
                $context->getParentConfig(),
                $context->getParentClassName(),
                $associationName,
                $context->isCollection()
            );
            $this->entityIdHelper->applyEntityIdentifierRestriction(
                $query,
                $context->getParentId(),
                $context->getParentMetadata(),
                $parentJoinAlias,
                self::PARENT_ENTITY_ID_QUERY_PARAM_NAME
            );
        } elseif (!$this->isParentEntityIdExistInQuery($query)) {
            $this->entityIdHelper->applyEntityIdentifierRestriction(
                $query,
                $context->getParentId(),
                $context->getParentMetadata(),
                'e',
                self::PARENT_ENTITY_ID_QUERY_PARAM_NAME
            );
        }
    }

    private function isParentEntityIdExistInQuery(QueryBuilder $query): bool
    {
        /** @var Parameter[] $parameters */
        $parameters = $query->getParameters();
        foreach ($parameters as $parameter) {
            if (str_starts_with($parameter->getName(), self::PARENT_ENTITY_ID_QUERY_PARAM_NAME)) {
                return true;
            }
        }

        return false;
    }

    private function joinParentEntity(
        QueryBuilder $query,
        string $queryRootAlias,
        EntityDefinitionConfig $parentConfig,
        string $parentClassName,
        string $associationName,
        bool $isCollection
    ): string {
        $parentJoinAlias = $queryRootAlias;
        $path = ConfigUtil::explodePropertyPath($associationName);
        $pathLength = \count($path);
        for ($i = 1; $i <= $pathLength; $i++) {
            $joinFieldName = $path[$pathLength - $i];
            $joinAlias = sprintf('parent_entity%d', $i);
            $parentPath = \array_slice($path, 0, -$i);
            if (empty($parentPath)) {
                $joinParentClassName = $parentClassName;
                if (!$this->doctrineHelper->isManageableEntityClass($joinParentClassName)) {
                    $parentResourceClass = $parentConfig->getParentResourceClass();
                    if ($parentResourceClass
                        && $this->doctrineHelper->isManageableEntityClass($parentResourceClass)
                    ) {
                        $joinParentClassName = $parentResourceClass;
                    }
                }
                $joinIsCollection = $isCollection;
            } else {
                $parentFieldConfig = $parentConfig->findFieldByPath($parentPath, true);
                $joinParentClassName = $parentFieldConfig->getTargetClass();
                $joinIsCollection = $parentFieldConfig->getTargetEntity()
                    ->findField($joinFieldName, true)
                    ->isCollectionValuedAssociation();
            }
            $this->addJoinToParentEntity(
                $query,
                $joinParentClassName,
                $joinFieldName,
                $joinIsCollection,
                $joinAlias,
                $parentJoinAlias
            );
            $parentJoinAlias = $joinAlias;
        }

        return $parentJoinAlias;
    }

    private function addJoinToParentEntity(
        QueryBuilder $query,
        string $parentClassName,
        string $associationName,
        bool $isCollection,
        string $joinAlias,
        string $parentJoinAlias = null
    ): void {
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

    private function getAssociationName(SubresourceContext $context): ?string
    {
        $associationName = $context->getAssociationName();
        $associationField = $context->getParentConfig()->getField($associationName);
        if (null === $associationField) {
            return null;
        }

        return $associationField->getPropertyPath($associationName);
    }

    private function getJoinFieldName(string $parentClassName, string $associationName): ?string
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
