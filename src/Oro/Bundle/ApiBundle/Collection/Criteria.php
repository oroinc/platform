<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\Common\Collections\Criteria as BaseCriteria;
use Doctrine\ORM\ORMException;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * Criteria for filtering data returned by ORM queries.
 */
class Criteria extends BaseCriteria
{
    public const ROOT_ALIAS_PLACEHOLDER   = '{root}';
    public const ENTITY_ALIAS_PLACEHOLDER = '{entity}';
    public const PLACEHOLDER_TEMPLATE     = '{%s}';

    /** @var EntityClassResolver */
    private $entityClassResolver;

    /** @var Join[] */
    private $joins = [];

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(EntityClassResolver $entityClassResolver)
    {
        parent::__construct();
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Determines whether a join for a given path exists.
     *
     * @param string $propertyPath The path for which a join should be applied.
     *
     * @return bool
     */
    public function hasJoin(string $propertyPath): bool
    {
        return isset($this->joins[$propertyPath]);
    }

    /**
     * Gets a join for a given path.
     *
     * @param string $propertyPath The path for which a join should be applied.
     *
     * @return Join|null
     */
    public function getJoin(string $propertyPath): ?Join
    {
        return $this->joins[$propertyPath] ?? null;
    }

    /**
     * Gets all joins.
     *
     * @return Join[] [path => Join, ...]
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * Adds an inner join.
     * The following placeholders should be used in $join and $condition:
     * * '{root}' for a root entity
     * * '{entity}' for a current joined entity
     * * '{property path}' for another join
     *
     * @param string      $propertyPath  The path for which the join should be applied.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     *
     * @return Join
     */
    public function addInnerJoin(
        string $propertyPath,
        string $join,
        string $conditionType = null,
        string $condition = null,
        string $indexBy = null
    ): Join {
        return $this->addJoin($propertyPath, Join::INNER_JOIN, $join, $conditionType, $condition, $indexBy);
    }

    /**
     * Adds a left join.
     * The following placeholders should be used in $join and $condition:
     * * '{root}' for a root entity
     * * '{entity}' for a current joined entity
     * * '{property path}' for another join
     *
     * @param string      $propertyPath  The path for which the join should be applied.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     *
     * @return Join
     */
    public function addLeftJoin(
        string $propertyPath,
        string $join,
        string $conditionType = null,
        string $condition = null,
        string $indexBy = null
    ): Join {
        return $this->addJoin($propertyPath, Join::LEFT_JOIN, $join, $conditionType, $condition, $indexBy);
    }

    /**
     * @param string      $propertyPath  The path for which the join should be applied.
     * @param string      $joinType      The condition type constant. Either Join::INNER_JOIN or Join::LEFT_JOIN.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     *
     * @return Join
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function addJoin(
        string $propertyPath,
        string $joinType,
        string $join,
        string $conditionType = null,
        string $condition = null,
        string $indexBy = null
    ): Join {
        if (!$propertyPath) {
            throw new \InvalidArgumentException('The property path must be not empty.');
        }
        if (!$join) {
            throw new \InvalidArgumentException(\sprintf(
                'The join must be be not empty. Join path: "%s".',
                $propertyPath
            ));
        }
        if (false === \strpos($join, '.')) {
            $entityClass = $this->resolveEntityClass($join);
            if (!$entityClass) {
                throw new \InvalidArgumentException(\sprintf(
                    '"%s" is not valid entity name. Join path: "%s".',
                    $join,
                    $propertyPath
                ));
            }
            $join = $entityClass;
        }
        if ($condition && !$conditionType) {
            throw new \InvalidArgumentException(\sprintf(
                'The condition type must be specified if the condition exists. Join path: "%s".',
                $propertyPath
            ));
        }

        $joinObject = new Join($joinType, $join, $conditionType, $condition, $indexBy);
        if (!isset($this->joins[$propertyPath])) {
            $this->joins[$propertyPath] = $joinObject;
        } else {
            $existingJoinObject = $this->joins[$propertyPath];
            if (!$existingJoinObject->equals($joinObject)) {
                throw new \LogicException(\sprintf(
                    'The join definition for "%s" conflicts with already added join. '
                    . 'Existing join: "%s". New join: "%s".',
                    $propertyPath,
                    (string)$existingJoinObject,
                    (string)$joinObject
                ));
            }

            $existingJoinType = $existingJoinObject->getJoinType();
            if ($existingJoinType !== $joinType && Join::LEFT_JOIN === $existingJoinType) {
                $existingJoinObject->setJoinType($joinObject->getJoinType());
            }
            $joinObject = $existingJoinObject;
        }

        return $joinObject;
    }

    /**
     * @param string $entityName
     *
     * @return string|null
     */
    private function resolveEntityClass(string $entityName): ?string
    {
        try {
            return $this->entityClassResolver->getEntityClass($entityName);
        } catch (ORMException $e) {
            return null;
        }
    }
}
