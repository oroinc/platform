<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\Common\Collections\Criteria as BaseCriteria;
use Doctrine\ORM\ORMException;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class Criteria extends BaseCriteria
{
    const ROOT_ALIAS_PLACEHOLDER   = '{root}';
    const ENTITY_ALIAS_PLACEHOLDER = '{entity}';
    const PLACEHOLDER_TEMPLATE     = '{%s}';

    /** @var EntityClassResolver */
    protected $entityClassResolver;

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
    public function hasJoin($propertyPath)
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
    public function getJoin($propertyPath)
    {
        return isset($this->joins[$propertyPath])
            ? $this->joins[$propertyPath]
            : null;
    }

    /**
     * Gets all joins.
     *
     * @return Join[] [path => Join, ...]
     */
    public function getJoins()
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
    public function addInnerJoin($propertyPath, $join, $conditionType = null, $condition = null, $indexBy = null)
    {
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
    public function addLeftJoin($propertyPath, $join, $conditionType = null, $condition = null, $indexBy = null)
    {
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
     */
    protected function addJoin(
        $propertyPath,
        $joinType,
        $join,
        $conditionType = null,
        $condition = null,
        $indexBy = null
    ) {
        if (!$propertyPath) {
            throw new \InvalidArgumentException('$propertyPath must be specified.');
        }
        if (!$join) {
            throw new \InvalidArgumentException(
                sprintf('$join must be specified. Join path: "%s".', $propertyPath)
            );
        } elseif (false === strpos($join, '.')) {
            $entityClass = $this->resolveEntityClass($join);
            if (!$entityClass) {
                throw new \InvalidArgumentException(
                    sprintf('"%s" is not valid entity name. Join path: "%s".', $join, $propertyPath)
                );
            }
            $join = $entityClass;
        }
        if ($condition && !$conditionType) {
            throw new \InvalidArgumentException(
                sprintf('$conditionType must be specified if $condition exists. Join path: "%s".', $propertyPath)
            );
        }

        $joinObject = new Join($joinType, $join, $conditionType, $condition, $indexBy);
        if (!isset($this->joins[$propertyPath])) {
            $this->joins[$propertyPath] = $joinObject;
        } else {
            $existingJoinObject = $this->joins[$propertyPath];
            if (!$existingJoinObject->equals($joinObject)) {
                throw new \LogicException(
                    sprintf(
                        'The join definition for "%s" conflicts with already added join. '
                        . 'Existing join: "%s". New join: "%s".',
                        $propertyPath,
                        (string)$existingJoinObject,
                        (string)$joinObject
                    )
                );
            }

            $existingJoinType = $existingJoinObject->getJoinType();
            if ($existingJoinType !== $joinType && $existingJoinType === Join::LEFT_JOIN) {
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
    protected function resolveEntityClass($entityName)
    {
        try {
            return $this->entityClassResolver->getEntityClass($entityName);
        } catch (ORMException $e) {
            return null;
        }
    }
}
