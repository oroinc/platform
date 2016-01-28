<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\Common\Collections\Criteria as BaseCriteria;
use Doctrine\ORM\ORMException;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class Criteria extends BaseCriteria
{
    const ROOT_ALIAS_PLACEHOLDER   = '{root}';
    const ENTITY_ALIAS_PLACEHOLDER = '{entity}';

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
     * @param string $path The path for which a join should be applied.
     *
     * @return bool
     */
    public function hasJoin($path)
    {
        return isset($this->joins[$path]);
    }

    /**
     * @param string $path The path for which a join should be applied.
     *
     * @return Join|null
     */
    public function getJoin($path)
    {
        return isset($this->joins[$path])
            ? $this->joins[$path]
            : null;
    }

    /**
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
     *
     * @param string      $path          The path for which the join should be applied.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     *
     * @return Join
     */
    public function addInnerJoin($path, $join, $conditionType = null, $condition = null, $indexBy = null)
    {
        return $this->addJoin($path, Join::INNER_JOIN, $join, $conditionType, $condition, $indexBy);
    }

    /**
     * Adds a left join.
     * The following placeholders should be used in $join and $condition:
     * * '{root}' for a root entity
     * * '{entity}' for a current joined entity
     *
     * @param string      $path          The path for which the join should be applied.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     *
     * @return Join
     */
    public function addLeftJoin($path, $join, $conditionType = null, $condition = null, $indexBy = null)
    {
        return $this->addJoin($path, Join::LEFT_JOIN, $join, $conditionType, $condition, $indexBy);
    }

    /**
     * @param string      $path          The path for which the join should be applied.
     * @param string      $joinType      The condition type constant. Either Join::INNER_JOIN or Join::LEFT_JOIN.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     *
     * @return Join
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addJoin($path, $joinType, $join, $conditionType = null, $condition = null, $indexBy = null)
    {
        if (!$path) {
            throw new \InvalidArgumentException('$path must be specified.');
        }
        if (!$join) {
            throw new \InvalidArgumentException(
                sprintf('$join must be specified. Join path: "%s".', $path)
            );
        }
        if ($condition && !$conditionType) {
            throw new \InvalidArgumentException(
                sprintf('$conditionType must be specified if $condition exists. Join path: "%s".', $path)
            );
        }

        if (null !== $conditionType && !$conditionType) {
            $conditionType = null;
        }
        if (null !== $condition && !$condition) {
            $condition = null;
        }
        if (null !== $indexBy && !$indexBy) {
            $indexBy = null;
        }
        if (null !== $conditionType && null === $condition) {
            $conditionType = null;
        }
        try {
            $join = $this->entityClassResolver->getEntityClass($join);
        } catch (ORMException $e) {
            throw new \InvalidArgumentException(
                sprintf('"%s" is not valid entity name. Join path: "%s".', $join, $path),
                0,
                $e
            );
        }

        if (!isset($this->joins[$path])) {
            $newJoin = new Join($joinType, $join, $conditionType, $condition, $indexBy);

            $this->joins[$path] = $newJoin;

            return $newJoin;
        }

        $existingJoin = $this->joins[$path];
        if ($existingJoin->getJoin() !== $join
            || $existingJoin->getConditionType() !== $conditionType
            || $existingJoin->getCondition() !== $condition
            || $existingJoin->getIndexBy() !== $indexBy
        ) {
            throw new \LogicException(
                sprintf(
                    'The join definition for "%s" conflicts with already added join. '
                    . 'Existing join: "%s". New join: "%s".',
                    $path,
                    (string)$existingJoin,
                    (string)(new Join($joinType, $join, $conditionType, $condition, $indexBy))
                )
            );
        }
        $existingJoinType = $existingJoin->getJoinType();
        if ($existingJoinType !== $joinType && $existingJoinType === Join::LEFT_JOIN) {
            $existingJoin->setJoinType($joinType);
        }

        return $existingJoin;
    }
}
