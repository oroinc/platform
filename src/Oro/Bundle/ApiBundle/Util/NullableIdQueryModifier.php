<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\EntitySerializer\DoctrineHelper as EntitySerializerDoctrineHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Modifies a query builder to filter out entities with null identifier values.
 */
class NullableIdQueryModifier implements QueryModifierInterface, QueryModifierOptionsAwareInterface
{
    private ?array $options = null;

    public function __construct(
        private readonly EntitySerializerDoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }

    #[\Override]
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        /** @var EntityConfig|null $config */
        $config = $this->options['config'] ?? null;
        if (null === $config) {
            return;
        }

        $resourceClass = $this->options['resourceClass'] ?? null;
        if (!$resourceClass) {
            return;
        }

        $resourceAlias = null;
        $idField = $this->getIdentifierField($config);
        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            $entityClass = $this->doctrineHelper->resolveEntityClass($from->getFrom());
            if ($entityClass === $resourceClass) {
                if ($resourceAlias) {
                    throw new \LogicException(\sprintf(
                        'Queries with several FROM parts for the same entity are not supported. Entity: %s.',
                        $entityClass
                    ));
                }
                $resourceAlias = $from->getAlias();
                if ($idField && $this->isFieldNullable($resourceClass, $idField)) {
                    $qb->andWhere($this->getIsNotNullExpr($qb, $from->getAlias(), $idField));
                }
            }
        }
        $this->updateJoins($qb, $config, $resourceAlias, $resourceClass, $idField);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function updateJoins(
        QueryBuilder $qb,
        EntityConfig $config,
        ?string $resourceAlias,
        string $resourceClass,
        ?string $idField
    ): void {
        /** @var Expr\Join[] $joins */
        foreach ($qb->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                $joinExpr = $join->getJoin();
                $delimiterPos = strpos($joinExpr, '.');
                if (false !== $delimiterPos) {
                    // process joins like "JOIN parentAlias.associationName AS alias"
                    $entityAlias = substr($joinExpr, 0, $delimiterPos);
                    $entityClass = QueryBuilderUtil::findClassByAlias($qb, $entityAlias);
                    if ($entityClass) {
                        $associationName = substr($joinExpr, $delimiterPos + 1);
                        $targetClass = $this->doctrineHelper->getEntityMetadata($entityClass)
                            ->getAssociationTargetClass($associationName);
                        if ($targetClass === $resourceClass) {
                            // process the case where there is an association with a root entity
                            if ($idField) {
                                $this->modifyJoinForNullableIdField($qb, $join, $targetClass, $idField);
                            }
                        } elseif ($resourceAlias && $entityAlias === $resourceAlias) {
                            // process the case where an associated entity ID can be NULL
                            $targetConfig = $config->findField($associationName, true)?->getTargetEntity();
                            if (null !== $targetConfig) {
                                $targetIdField = $this->getIdentifierField($targetConfig);
                                if ($targetIdField) {
                                    $this->modifyJoinForNullableIdField($qb, $join, $targetClass, $targetIdField);
                                }
                            }
                        }
                    }
                } elseif ($idField) {
                    // process joins like "JOIN EntityClass AS alias WITH ..."
                    $targetClass = $this->doctrineHelper->resolveEntityClass($joinExpr);
                    if ($targetClass === $resourceClass) {
                        $this->modifyJoinForNullableIdField($qb, $join, $targetClass, $idField);
                    }
                }
            }
        }
    }

    private function getIdentifierField(EntityConfig $config): ?string
    {
        $idFieldNames = $config->get(ConfigUtil::IDENTIFIER_FIELD_NAMES);
        if (!$idFieldNames || \count($idFieldNames) > 1) {
            return null;
        }

        $idFieldName = $idFieldNames[0];
        $idField = $config->getField($idFieldName);
        if (null === $idField) {
            return $idFieldName;
        }

        return $idField->getPropertyPath($idFieldName);
    }

    private function isFieldNullable(string $entityClass, string $fieldName): bool
    {
        return $this->doctrineHelper->getEntityMetadata($entityClass)->isFieldNullable($fieldName);
    }

    private function getIsNotNullExpr(QueryBuilder $qb, string $alias, string $fieldName): string
    {
        return $qb->expr()->isNotNull($alias . '.' . $fieldName);
    }

    private function modifyJoinForNullableIdField(
        QueryBuilder $qb,
        Expr\Join $join,
        string $entityClass,
        string $idFieldName
    ): void {
        if (!$this->isFieldNullable($entityClass, $idFieldName)) {
            return;
        }

        $existingCondition = $join->getCondition();
        if (!\is_string($existingCondition)) {
            if (!$existingCondition && Expr\Join::WITH !== $join->getConditionType()) {
                $existingCondition = '';
            } else {
                throw new \LogicException(\sprintf(
                    'A condition for the "%s AS %s" join should be a string.',
                    $join->getJoin(),
                    $join->getAlias()
                ));
            }
        }

        $isNotNullCondition = $this->getIsNotNullExpr($qb, $join->getAlias(), $idFieldName);
        $this->changeJoinProperty(
            $join,
            'condition',
            $existingCondition ? $existingCondition . ' AND ' . $isNotNullCondition : $isNotNullCondition
        );
        if (Expr\Join::WITH !== $join->getConditionType()) {
            $this->changeJoinProperty($join, 'conditionType', Expr\Join::WITH);
        }
    }

    private function changeJoinProperty(Expr\Join $join, string $propertyName, mixed $propertyValue): void
    {
        $property = ReflectionUtil::getProperty(new \ReflectionClass($join), $propertyName);
        if (null === $property) {
            throw new \LogicException(\sprintf(
                'The "%s" property does not exist in %s.',
                $propertyName,
                \get_class($join)
            ));
        }
        $property->setValue($join, $propertyValue);
    }
}
