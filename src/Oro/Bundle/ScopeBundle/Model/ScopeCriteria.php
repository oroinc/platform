<?php

namespace Oro\Bundle\ScopeBundle\Model;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Contains a set of parameters to filter Scope entities
 * and provides methods to apply these parameters to scope related parts of ORM query.
 *
 * Note: parameters are sorted by priority,
 * the higher the priority, the closer the parameter to the top of the parameter list.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ScopeCriteria implements \IteratorAggregate
{
    use NormalizeParameterValueTrait;

    public const IS_NOT_NULL = 'IS_NOT_NULL';

    /** @var array [parameter name => parameter value, ...] */
    private $parameters;

    /** @var ClassMetadataFactory */
    private $classMetadataFactory;

    /** @var string|null */
    private $identifier;

    /**
     * @param array                $parameters           [parameter name => parameter value, ...]
     * @param ClassMetadataFactory $classMetadataFactory The ORM metadata factory
     */
    public function __construct(array $parameters, ClassMetadataFactory $classMetadataFactory)
    {
        $this->parameters = $parameters;
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * Gets unique identifier of a set of parameters represented by this criteria object.
     */
    public function getIdentifier(): string
    {
        if (null === $this->identifier) {
            $this->identifier = $this->buildIdentifier();
        }

        return $this->identifier;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string[]     $ignoreFields
     */
    public function applyWhere(QueryBuilder $qb, string $alias, array $ignoreFields = []): void
    {
        $this->doApplyWhere($qb, $alias, $ignoreFields, false);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string[]     $ignoreFields
     */
    public function applyWhereWithPriority(QueryBuilder $qb, string $alias, array $ignoreFields = []): void
    {
        $this->doApplyWhere($qb, $alias, $ignoreFields, true);
    }

    public function applyWhereWithPriorityForScopes(
        QueryBuilder $qb,
        string $alias,
        array $ignoreFields = []
    ): void {
        $this->applyWhereWithPriority($qb, $alias, $ignoreFields);

        $orX = $qb->expr()->orX();
        foreach ($this->parameters as $field => $value) {
            if (in_array($field, $ignoreFields)) {
                continue;
            }

            QueryBuilderUtil::checkIdentifier($alias);
            QueryBuilderUtil::checkIdentifier($field);
            $aliasedField = $alias . '.' . $field;
            $orX->add($qb->expr()->isNotNull($aliasedField));
        }

        $fieldId = 'id';
        QueryBuilderUtil::checkIdentifier($fieldId);
        $groupBy = $alias . '.' . $fieldId;

        $qb->andWhere($orX)->groupBy($groupBy);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string[]     $ignoreFields
     */
    public function applyToJoin(QueryBuilder $qb, string $alias, array $ignoreFields = []): void
    {
        /** @var Join[] $joins */
        $joins = $qb->getDQLPart('join');
        $qb->resetDQLPart('join');
        $this->reapplyJoins($qb, $joins, $alias, $ignoreFields, false);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string[]     $ignoreFields
     */
    public function applyToJoinWithPriority(QueryBuilder $qb, string $alias, array $ignoreFields = []): void
    {
        /** @var Join[] $joins */
        $joins = $qb->getDQLPart('join');
        $qb->resetDQLPart('join');
        $this->reapplyJoins($qb, $joins, $alias, $ignoreFields, true);
    }

    /**
     * Returns all parameters.
     * The parameters are sorted by priority, the higher the priority,
     * the closer the parameter to the top of the parameter list.
     */
    public function toArray(): array
    {
        return $this->parameters;
    }

    /**
     * Returns an iterator by parameters are sorted by priority.
     * The higher the priority, the earlier the parameter is returned by the iterator.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->parameters);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string[]     $ignoreFields
     * @param bool         $withPriority
     */
    private function doApplyWhere(
        QueryBuilder $qb,
        string $alias,
        array $ignoreFields,
        bool $withPriority
    ): void {
        $scopeClassMetadata = $this->getClassMetadata(Scope::class);
        QueryBuilderUtil::checkIdentifier($alias);
        foreach ($this->parameters as $field => $value) {
            QueryBuilderUtil::checkIdentifier($field);
            if (\in_array($field, $ignoreFields, true)) {
                continue;
            }
            $condition = null;
            if ($this->isCollectionValuedAssociation($scopeClassMetadata, $field)) {
                $localAlias = $alias . '_' . $field;
                $condition = $this->resolveBasicCondition($qb, $localAlias, 'id', $value, $withPriority);
                $qb->leftJoin($alias . '.' . $field, $localAlias, Join::WITH, $condition);
            } else {
                $condition = $this->resolveBasicCondition($qb, $alias, $field, $value, $withPriority);
            }
            $qb->andWhere($condition);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param Join[]       $joins
     * @param string       $alias
     * @param string[]     $ignoreFields
     * @param bool         $withPriority
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function reapplyJoins(
        QueryBuilder $qb,
        array $joins,
        string $alias,
        array $ignoreFields,
        bool $withPriority
    ): void {
        $scopeClassMetadata = $this->getClassMetadata(Scope::class);
        QueryBuilderUtil::checkIdentifier($alias);
        foreach ($joins as $join) {
            if (\is_array($join)) {
                $this->reapplyJoins($qb, $join, $alias, $ignoreFields, $withPriority);
                continue;
            }

            $parts = [];
            $additionalJoins = [];
            $joinCondition = $join->getCondition();
            if ($joinCondition) {
                $parts[] = $joinCondition;
            }
            if ($join->getAlias() === $alias) {
                $usedFields = [];
                if ($joinCondition) {
                    $usedFields = $this->getUsedFields($joinCondition, $alias);
                }
                foreach ($this->parameters as $field => $value) {
                    if (\in_array($field, $ignoreFields, true) || \in_array($field, $usedFields, true)) {
                        continue;
                    }
                    if ($this->isCollectionValuedAssociation($scopeClassMetadata, $field)) {
                        $additionalJoins[$field] = $this->resolveBasicCondition(
                            $qb,
                            $alias . '_' . $field,
                            'id',
                            $value,
                            $withPriority
                        );
                    } else {
                        $parts[] = $this->resolveBasicCondition($qb, $alias, $field, $value, $withPriority);
                    }
                }
            }

            $condition = $this->getConditionFromParts($parts, $withPriority);
            $this->applyJoinWithModifiedCondition($qb, $condition, $join);
            if (!empty($additionalJoins)) {
                $additionalJoins = array_filter($additionalJoins);
                foreach ($additionalJoins as $field => $condition) {
                    QueryBuilderUtil::checkIdentifier($field);
                    $qb->leftJoin($alias . '.' . $field, $alias . '_' . $field, Join::WITH, $condition);
                    if (!$withPriority) {
                        $qb->andWhere($condition);
                    }
                }
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param string       $field
     * @param mixed        $value
     * @param bool         $withPriority
     *
     * @return mixed
     */
    private function resolveBasicCondition(
        QueryBuilder $qb,
        string $alias,
        string $field,
        $value,
        bool $withPriority
    ) {
        QueryBuilderUtil::checkIdentifier($alias);
        QueryBuilderUtil::checkIdentifier($field);

        $aliasedField = $alias . '.' . $field;
        if ($value === null) {
            $part = $qb->expr()->isNull($aliasedField);
        } elseif ($value === self::IS_NOT_NULL) {
            $part = $qb->expr()->isNotNull($aliasedField);
        } else {
            $paramName = $alias . '_param_' . $field;
            if (\is_array($value)) {
                $comparisonCondition = $qb->expr()->in($aliasedField, ':' . $paramName);
            } else {
                $comparisonCondition = $qb->expr()->eq($aliasedField, ':' . $paramName);
            }
            if ($withPriority) {
                $part = $qb->expr()->orX(
                    $comparisonCondition,
                    $qb->expr()->isNull($aliasedField)
                );
            } else {
                $part = $comparisonCondition;
            }
            $qb->setParameter($paramName, $value);
            if ($withPriority) {
                $qb->addOrderBy($aliasedField, Criteria::DESC);
            }
        }

        return $part;
    }

    private function getConditionFromParts(array $parts, bool $withPriority): string
    {
        if ($withPriority) {
            $parts = array_map(
                function ($part) {
                    return '(' . $part . ')';
                },
                $parts
            );
        }

        return implode(' AND ', $parts);
    }

    private function applyJoinWithModifiedCondition(QueryBuilder $qb, string $condition, Join $join): void
    {
        if (Join::INNER_JOIN === $join->getJoinType()) {
            $qb->innerJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $condition,
                $join->getIndexBy()
            );
        }
        if (Join::LEFT_JOIN === $join->getJoinType()) {
            $qb->leftJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $condition,
                $join->getIndexBy()
            );
        }
    }

    /**
     * @param string $condition
     * @param string $alias
     *
     * @return string[]
     */
    private function getUsedFields(string $condition, string $alias): array
    {
        $fields = [];
        $parts = explode(' AND ', $condition);
        foreach ($parts as $part) {
            $matches = [];
            preg_match(sprintf('/%s\.\w+/', $alias), $part, $matches);
            foreach ($matches as $match) {
                $fields[] = explode('.', $match)[1];
            }
        }

        return $fields;
    }

    private function getClassMetadata(string $entityClass): ClassMetadata
    {
        return $this->classMetadataFactory->getMetadataFor($entityClass);
    }

    private function isCollectionValuedAssociation(ClassMetadata $classMetadata, string $field): bool
    {
        if (!$classMetadata->hasAssociation($field)) {
            return false;
        }

        return $classMetadata->isCollectionValuedAssociation($field);
    }

    private function buildIdentifier(): string
    {
        $result = '';
        foreach ($this->parameters as $field => $value) {
            $result .= sprintf('%s=%s;', $field, $this->normalizeParameterValue($value));
        }

        return $result;
    }

    /**
     * @param object $entity
     *
     * @return mixed
     */
    private function getEntityId($entity)
    {
        $classMetadata = $this->getClassMetadata(ClassUtils::getClass($entity));
        $id = $classMetadata->getFieldValue($entity, $classMetadata->getSingleIdentifierFieldName());
        if (null === $id) {
            $id = spl_object_hash($entity);
        }

        return $id;
    }
}
