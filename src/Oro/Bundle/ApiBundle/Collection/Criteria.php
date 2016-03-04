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

    /** @var string */
    protected $joinAliasTemplate;

    /** @var Join[] */
    private $joins = [];

    /**
     * @param EntityClassResolver $entityClassResolver
     * @param string              $joinAliasTemplate
     */
    public function __construct(EntityClassResolver $entityClassResolver, $joinAliasTemplate = 'alias%d')
    {
        parent::__construct();
        $this->entityClassResolver = $entityClassResolver;
        $this->joinAliasTemplate   = $joinAliasTemplate;
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
     * Makes sure that this criteria object contains all required joins and aliases are set for all joins.
     */
    public function completeJoins()
    {
        $this->ensureJoinAliasesSet();
        $pathMap = $this->getJoinPathMap();
        if (!empty($pathMap)) {
            $this->sortJoinPathMap($pathMap);
            foreach ($pathMap as $path => $item) {
                if (!$this->hasJoin($path)) {
                    $parentAlias = empty($item['parent'])
                        ? self::ROOT_ALIAS_PLACEHOLDER
                        : $this->getJoin(implode('.', $item['parent']))->getAlias();
                    $this
                        ->addLeftJoin($path, $parentAlias . '.' . $item['field'])
                        ->setAlias($item['field']);
                }
            }
        }
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

    /**
     * Sets missing join aliases
     */
    protected function ensureJoinAliasesSet()
    {
        $counter = 0;
        $joins   = $this->getJoins();
        foreach ($joins as $join) {
            $counter++;
            if (!$join->getAlias()) {
                $join->setAlias(sprintf($this->joinAliasTemplate, $counter));
            }
        }
    }

    /**
     * @return array [path => ['field' => string, 'parent' => [...]], ...]
     */
    protected function getJoinPathMap()
    {
        $fields = $this->getFields();

        $pathMap = [];
        foreach ($fields as $field) {
            $lastDelimiter = strrpos($field, '.');
            if (false !== $lastDelimiter) {
                $path = substr($field, 0, $lastDelimiter);
                if (!isset($pathMap[$path])) {
                    $pathMap[$path] = $this->buildJoinPathMapValue($path);
                }
            }
        }
        $joinPaths = array_keys($this->getJoins());
        foreach ($joinPaths as $path) {
            if (!isset($pathMap[$path])) {
                $pathMap[$path] = $this->buildJoinPathMapValue($path);
            }
        }

        return $pathMap;
    }

    /**
     * @return string[]
     */
    protected function getFields()
    {
        $fields    = [];
        $whereExpr = $this->getWhereExpression();
        if ($whereExpr) {
            $visitor = new FieldVisitor();
            $visitor->dispatch($whereExpr);
            $fields = $visitor->getFields();
        }
        $orderBy = $this->getOrderings();
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                if (!in_array($field, $fields, true)) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * @param string $propertyPath
     *
     * @return array
     */
    protected function buildJoinPathMapValue($propertyPath)
    {
        $lastDelimiter = strrpos($propertyPath, '.');
        if (false === $lastDelimiter) {
            return [
                'field'  => $propertyPath,
                'parent' => []
            ];
        } else {
            return [
                'field'  => substr($propertyPath, $lastDelimiter + 1),
                'parent' => explode('.', $propertyPath)
            ];
        }
    }

    /**
     * @param array $pathMap
     */
    protected function sortJoinPathMap(array &$pathMap)
    {
        uasort(
            $pathMap,
            function (array $a, array $b) {
                $aCount = count($a['parent']);
                $bCount = count($b['parent']);
                if ($aCount === $bCount) {
                    return 0;
                }

                return ($aCount < $bCount) ? -1 : 1;
            }
        );
    }
}
