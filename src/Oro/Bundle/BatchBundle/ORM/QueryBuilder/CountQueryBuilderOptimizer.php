<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

class CountQueryBuilderOptimizer
{
    /** @var string */
    protected $idFieldName;

    /** @var string */
    protected $rootAlias;

    /** @var QueryBuilder */
    protected $originalQb;

    /** @var QueryBuilderTools */
    protected $qbTools;

    /** @var ClassMetadataFactory */
    protected $metadataFactory;

    /**
     * @param QueryBuilderTools|null $qbTools
     */
    public function __construct(QueryBuilderTools $qbTools = null)
    {
        if (!$qbTools) {
            $qbTools = new QueryBuilderTools();
        }
        $this->qbTools = $qbTools;
    }

    /**
     * Set original query builder.
     *
     * @param QueryBuilder $originalQb
     */
    protected function setOriginalQueryBuilder(QueryBuilder $originalQb)
    {
        $this->originalQb = $originalQb;

        $this->qbTools->prepareFieldAliases($originalQb->getDQLPart('select'));
        $this->qbTools->prepareJoinTablePaths($originalQb->getDQLPart('join'));
        $this->rootAlias = current($this->originalQb->getRootAliases());
        $this->initIdFieldName();

        $this->metadataFactory = $this->originalQb->getEntityManager()->getMetadataFactory();

        // make sure that metadata factory is initialized
        $this->metadataFactory->getAllMetadata();
    }

    /**
     * Get optimized query builder for count calculation.
     *
     * @param QueryBuilder $originalQb
     * @return QueryBuilder
     */
    public function getCountQueryBuilder(QueryBuilder $originalQb)
    {
        $this->setOriginalQueryBuilder($originalQb);
        $parts = $this->originalQb->getDQLParts();

        $qb = clone $this->originalQb;
        $qb->setFirstResult(null)
            ->setMaxResults(null)
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->resetDQLPart('select')
            ->resetDQLPart('join')
            ->resetDQLPart('where')
            ->resetDQLPart('having');

        $fieldsToSelect = array();
        if ($parts['groupBy']) {
            $groupBy = (array) $parts['groupBy'];
            $groupByFields = $this->getSelectFieldFromGroupBy($groupBy);
            $usedGroupByAliases = [];
            foreach ($groupByFields as $key => $groupByField) {
                $alias = '_groupByPart' . $key;
                $usedGroupByAliases[] = $alias;
                $fieldsToSelect[] = $groupByField . ' as ' . $alias;
            }
            $qb->groupBy(implode(', ', $usedGroupByAliases));
        } elseif (!$parts['where'] && $parts['having']) {
            // If there is no where and group by, but having is present - convert having to where.
            $parts['where'] = $parts['having'];
            $parts['having'] = null;
            $qb->resetDQLPart('having');
        }

        if ($parts['having']) {
            $qb->having(
                $this->qbTools->replaceAliasesWithFields($parts['having'])
            );
        }

        if ($parts['join']) {
            $this->addJoins($qb, $parts);
        }
        if (!$parts['groupBy']) {
            $fieldsToSelect[] = $this->getFieldFQN($this->idFieldName);
        }

        if ($parts['where']) {
            $qb->where($this->qbTools->replaceAliasesWithFields($parts['where']));
        }

        $qb->select(array_unique($fieldsToSelect));
        $this->qbTools->fixUnusedParameters($qb);

        return $qb;
    }

    /**
     * Add required JOINs to resulting Query Builder.
     *
     * @param QueryBuilder $qb
     * @param array $parts
     */
    protected function addJoins(QueryBuilder $qb, array $parts)
    {
        // Collect list of tables which should be added to new query
        $requiredToJoin = $this->qbTools->getUsedTableAliases($parts['where']);
        $groupByAliases = $this->qbTools->getUsedTableAliases($parts['groupBy']);
        $requiredToJoin = array_merge($requiredToJoin, $groupByAliases);
        $requiredToJoin = array_merge($requiredToJoin, $this->qbTools->getUsedTableAliases($parts['having']));
        $requiredToJoin = array_merge(
            $requiredToJoin,
            $this->qbTools->getUsedJoinAliases($parts['join'], $requiredToJoin, $this->rootAlias)
        );
        $requiredToJoin = array_merge(
            $requiredToJoin,
            $this->getNonSymmetricJoinAliases($parts['join'], $parts['from'], $groupByAliases)
        );

        $requiredToJoin = array_diff(array_unique($requiredToJoin), array($this->rootAlias));

        /** @var Expr\Join $join */
        foreach ($parts['join'][$this->rootAlias] as $join) {
            $alias     = $join->getAlias();
            // To count results number join all tables with inner join and required to tables
            if ($join->getJoinType() === Expr\Join::INNER_JOIN || in_array($alias, $requiredToJoin, true)) {
                $condition = $this->qbTools->replaceAliasesWithFields($join->getCondition());
                $condition = $this->qbTools->replaceAliasesWithJoinPaths($condition);

                if ($join->getJoinType() === Expr\Join::INNER_JOIN) {
                    $qb->innerJoin(
                        $join->getJoin(),
                        $alias,
                        $join->getConditionType(),
                        $condition,
                        $join->getIndexBy()
                    );
                } else {
                    $qb->leftJoin(
                        $join->getJoin(),
                        $alias,
                        $join->getConditionType(),
                        $condition,
                        $join->getIndexBy()
                    );
                }
            }
        }
    }

    /**
     * Initialize the column id of the targeted class.
     *
     * @return string
     */
    protected function initIdFieldName()
    {
        /** @var Expr\From $from */
        $from = current($this->originalQb->getDQLPart('from'));
        $class = $from->getFrom();

        $idNames = $this->originalQb
            ->getEntityManager()
            ->getMetadataFactory()
            ->getMetadataFor($class)
            ->getIdentifierFieldNames();

        $this->idFieldName = current($idNames);
    }

    /**
     * Get fields fully qualified name
     *
     * @param string $fieldName
     * @return string
     */
    protected function getFieldFQN($fieldName)
    {
        if (strpos($fieldName, '.') === false) {
            $fieldName = $this->rootAlias . '.' . $fieldName;
        }

        return $fieldName;
    }

    /**
     * @param GroupBy[] $groupBy
     * @return array
     */
    protected function getSelectFieldFromGroupBy(array $groupBy)
    {
        $expressions = array();
        foreach ($groupBy as $groupByPart) {
            foreach ($groupByPart->getParts() as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($part));
            }
        }

        return $expressions;
    }

    /**
     * @param string $groupByPart
     * @return array
     */
    protected function getSelectFieldFromGroupByPart($groupByPart)
    {
        $expressions = array();
        if (strpos($groupByPart, ',') !== false) {
            $groupByParts = explode(',', $groupByPart);
            foreach ($groupByParts as $part) {
                $expressions = array_merge($expressions, $this->getSelectFieldFromGroupByPart($part));
            }
        } else {
            $groupByPart = trim($groupByPart);
            $groupByPart = $this->qbTools->replaceAliasesWithFields($groupByPart);
            $expressions[] = $groupByPart;
        }

        return $expressions;
    }

    /**
     * Get join aliases that will produce non symmetric results
     * (many-to-one left join or one-to-many right join)
     *
     * @param array       $joins
     * @param Expr\From[] $fromStatements
     * @param string      $groupByAliases the aliases that was used in GROUP BY statement
     *
     * @return array
     */
    protected function getNonSymmetricJoinAliases($joins, $fromStatements, $groupByAliases)
    {
        // collect the correspondence of select alias to it's entity class
        // classes are sometimes arrive in short form (`OroEmailBundle:Email`)
        // and sometimes there is relation (e. g. `a.owner`) instead of class name
        $aliasToClass = [];
        foreach ($fromStatements as $from) {
            /* @var Expr\From $from */
            $aliasToClass[$from->getAlias()] = $this->resolveEntityClass($from->getFrom());
        }
        foreach ($joins[$this->rootAlias] as $join) {
            /* @var Expr\Join $join */
            $aliasToClass[$join->getAlias()] = $this->resolveEntityClass($join->getJoin());
        }

        $aliases      = [];
        $dependencies = $this->qbTools->getAllDependencies($this->rootAlias, $joins[$this->rootAlias]);

        foreach ($dependencies as $alias => $joinInfo) {
            /** @var Expr\Join $joinExpr */
            // find a join statement that corresponds to current alias
            $joinExpr = current(array_filter($joins[$this->rootAlias], function (Expr\Join $join) use ($alias) {
                return $join->getAlias() === $alias;
            }));

            // skip joins that is not left joins or was not used in GROUP BY statement
            // if it was a GROUP BY statement at all
            if ($joinExpr->getJoinType() !== 'LEFT'
                || (count($groupByAliases) && !in_array($alias, $groupByAliases, true))) {
                continue;
            }

            // association name or class name
            $join = $joinExpr->getJoin();

            $parts = explode('.', $join);

            // if there is just association expression like `a.owner`
            if (count($parts) === 2) {
                list($associationAlias, $associationName) = $parts;
                $className = $this->resolveEntityClassByAlias(
                    $associationAlias,
                    $associationName,
                    $aliasToClass
                );

                if ($this->isToManyAssociation($className, $associationName)) {
                    $aliases[] = $alias;
                }

            } else { // otherwise there is a class name
                // join condition (e. g. `e.user = u`) or null
                $condition = $joinExpr->getCondition();
                $dependantClass = $this->resolveEntityClass($join);
                $aliasToAssociation = [];

                foreach ($this->qbTools->getFields($condition) as $field) {
                    list($associationAlias, $associationName) = explode('.', $field);
                    $aliasToAssociation[$associationAlias] = $associationName;
                }

                // for each dependant alias resolve it's entity class and check the relations
                foreach ($joinInfo[1] as $dependantAlias) {
                    $aliasToCheck = array_key_exists($dependantAlias, $aliasToAssociation)
                        ? $dependantAlias
                        : $alias;

                    $leftClass = $this->resolveEntityClassByAlias(
                        $dependantAlias,
                        $aliasToAssociation[$aliasToCheck],
                        $aliasToClass
                    );

                    $plainAssociation = $this->isToManyAssociation(
                        $leftClass,
                        $aliasToAssociation[$aliasToCheck],
                        $dependantClass
                    );

                    $inverseAssociation = $this->isToManyAssociation(
                        $dependantClass,
                        $aliasToAssociation[$aliasToCheck],
                        $leftClass,
                        true
                    );

                    if ($plainAssociation || $inverseAssociation) {
                        $aliases[] = $alias;
                    }
                }
            }
        }

        return array_unique($aliases);
    }

    /**
     * Gets the full class name if the given join represents an entity name in form "bundle:class"
     *
     * @param string $join
     *
     * @return string
     */
    protected function resolveEntityClass($join)
    {
        $parts = explode(':', $join);
        if (count($parts) === 2) {
            $join = $this->originalQb
                ->getEntityManager()
                ->getConfiguration()
                ->getEntityNamespace($parts[0]) . '\\' . $parts[1];
        }

        return $join;
    }

    /**
     * Resolve the FQCN by it's alias and association name
     *
     * @param $alias
     * @param $associationName
     * @param $aliasToClass
     * @param $metadataFactory
     */
    protected function resolveEntityClassByAlias($alias, $associationName, $aliasToClass)
    {
        $expression = $aliasToClass[$alias];

        while (count(explode('.', $expression)) === 2) {
            list($mediateAlias, $mediateAssociationName) = explode('.', $expression);
            $expression = $aliasToClass[$mediateAlias];

            /** @var ClassMetadata $metadata */
            $metadata = $this->metadataFactory->getMetadataFor($expression);
            $associationMapping = $metadata->getAssociationMapping($mediateAssociationName);
            $expression = $associationMapping['sourceEntity'];

            $metadata = $this->metadataFactory->getMetadataFor($expression);
            if (!$metadata->hasAssociation($associationName)) {
                $expression = $associationMapping['targetEntity'];
            }
        }

        return $expression;
    }

    /**
     * Check if class A has a to-many relation to class B for particular association
     * or vice versa if we are checking inverse association
     *
     * @param string $entityClassA
     * @param string|null $entityClassB
     * @param string $associationName
     * @param $metadataFactory
     * @param bool|false $inverse
     * @return bool
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function isToManyAssociation($entityClassA, $associationName, $entityClassB = null, $inverse = false)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->metadataFactory->getMetadataFor($entityClassA);
        $checkAgainst = [ClassMetadata::MANY_TO_MANY];

        $checkAgainst[] = $inverse
            ? ClassMetadata::MANY_TO_ONE
            : ClassMetadata::ONE_TO_MANY;

        $associations = $entityClassB
            ? $associations = $metadata->getAssociationsByTargetClass($entityClassB)
            : [$associationName => $metadata->getAssociationMapping($associationName)];

        return array_key_exists($associationName, $associations)
               && in_array($associations[$associationName]['type'], $checkAgainst, true);
    }
}
