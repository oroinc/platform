<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class QueryOptimizationContext
{
    /** @var QueryBuilder */
    protected $originalQueryBuilder;

    /** @var QueryBuilderTools */
    protected $qbTools;

    /** @var array [alias => class_name, ...] */
    private $aliasToClassMap;

    /** @var array [alias => Expr\Join, ...] */
    private $aliasToJoinMap;

    /**
     * @param QueryBuilder      $queryBuilder
     * @param QueryBuilderTools $qbTools
     */
    public function __construct(QueryBuilder $queryBuilder, QueryBuilderTools $qbTools)
    {
        // make sure 'from' DQL part is initialized for both original and optimized query builders
        $queryBuilder->getRootEntities();

        $this->originalQueryBuilder  = $queryBuilder;
        $this->qbTools               = $qbTools;

        // initialize the query builder helper
        $this->qbTools->prepareFieldAliases($this->originalQueryBuilder->getDQLPart('select'));
        $this->qbTools->prepareJoinTablePaths($this->originalQueryBuilder->getDQLPart('join'));
    }

    /**
     * @return QueryBuilder
     */
    public function getOriginalQueryBuilder()
    {
        return $this->originalQueryBuilder;
    }

    /**
     * Gets the ORM metadata descriptor for the given entity
     *
     * @param string $entityClass
     *
     * @return ClassMetadata
     */
    public function getClassMetadata($entityClass)
    {
        return $this->originalQueryBuilder->getEntityManager()->getClassMetadata($entityClass);
    }

    /**
     * Gets a join statement that corresponds to the given alias
     *
     * @param string $alias
     *
     * @return Expr\Join
     *
     * @throws \InvalidArgumentException if an alias does not exist
     */
    public function getJoinByAlias($alias)
    {
        $aliasToJoinMap = $this->getAliasToJoinMap();
        if (!isset($aliasToJoinMap[$alias])) {
            throw $this->createUnknownAliasException($alias, array_keys($aliasToJoinMap));
        }

        return $aliasToJoinMap[$alias];
    }

    /**
     * Gets aliases of all entities exist in original query, except aliases from sub queries
     *
     * @return string[]
     */
    public function getAliases()
    {
        return array_keys($this->getAliasToJoinMap());
    }

    /**
     * Gets an entity class name that corresponds to the given alias
     *
     * @param string $alias
     *
     * @return string
     *
     * @throws \InvalidArgumentException if an alias does not exist
     */
    public function getEntityClassByAlias($alias)
    {
        $aliasToClassMap = $this->getAliasToClassMap();
        if (!isset($aliasToClassMap[$alias])) {
            throw $this->createUnknownAliasException($alias, array_keys($aliasToClassMap));
        }

        return $aliasToClassMap[$alias];
    }

    /**
     * Gets the full class name of a target entity of the given association
     *
     * @param string $associationAlias
     * @param string $associationName
     *
     * @return string
     *
     * @throws \InvalidArgumentException if an alias does not exist
     * @throws \Doctrine\ORM\Mapping\MappingException if an association does not exist
     */
    public function getAssociationTargetEntityClass($associationAlias, $associationName)
    {
        $associationMapping = $this->getClassMetadata($this->getEntityClassByAlias($associationAlias))
            ->getAssociationMapping($associationName);

        return $associationMapping['targetEntity'];
    }

    /**
     * Gets the type of the given association
     *
     * @param string $associationAlias
     * @param string $associationName
     *
     * @return int
     *
     * @throws \InvalidArgumentException if an alias does not exist
     * @throws \Doctrine\ORM\Mapping\MappingException if an association does not exist
     */
    public function getAssociationType($associationAlias, $associationName)
    {
        $associationMapping = $this->getClassMetadata($this->getEntityClassByAlias($associationAlias))
            ->getAssociationMapping($associationName);

        return $associationMapping['type'];
    }

    /**
     * @return array [alias => Expr\Join, ...]
     */
    protected function getAliasToJoinMap()
    {
        if (null !== $this->aliasToJoinMap) {
            return $this->aliasToJoinMap;
        }

        $this->aliasToJoinMap = [];

        $joins = $this->originalQueryBuilder->getDQLPart('join');
        /* @var Expr\Join[] $childJoins */
        foreach ($joins as $rootAlias => $childJoins) {
            foreach ($childJoins as $join) {
                $this->aliasToJoinMap[$join->getAlias()] = $join;
            }
        }

        return $this->aliasToJoinMap;
    }

    /**
     * @return array [alias => class_name, ...]
     */
    protected function getAliasToClassMap()
    {
        if (null !== $this->aliasToClassMap) {
            return $this->aliasToClassMap;
        }

        $this->aliasToClassMap = [];

        $aliasToJoinExprMap = $this->getAliasToJoinExprMap();
        $toRemoveAliases    = [];

        foreach ($aliasToJoinExprMap as $alias => $joinExpr) {
            if (false === strpos($joinExpr, '.')) {
                $this->aliasToClassMap[$alias] = $this->resolveEntityClass($joinExpr);
                $toRemoveAliases[]             = $alias;
            }
        }

        while (!empty($toRemoveAliases)) {
            foreach ($toRemoveAliases as $alias) {
                unset($aliasToJoinExprMap[$alias]);
            }
            $toRemoveAliases = [];

            foreach ($aliasToJoinExprMap as $alias => $joinExpr) {
                $exprParts = explode('.', $joinExpr);
                $joinAlias = $exprParts[0];
                if (isset($this->aliasToClassMap[$alias]) || !isset($this->aliasToClassMap[$joinAlias])) {
                    continue;
                }
                $associationMapping            = $this->getClassMetadata($this->aliasToClassMap[$joinAlias])
                    ->getAssociationMapping($exprParts[1]);
                $this->aliasToClassMap[$alias] = $associationMapping['targetEntity'];
                $toRemoveAliases[]             = $alias;
            }
        }

        return $this->aliasToClassMap;
    }

    /**
     * Collects the correspondence of select alias to it's join expression
     *
     * @return array [alias => joinExpr, ...]
     */
    protected function getAliasToJoinExprMap()
    {
        $result = [];
        /** @var Expr\From[] $fromStatements */
        $fromStatements = $this->originalQueryBuilder->getDQLPart('from');
        foreach ($fromStatements as $from) {
            $result[$from->getAlias()] = $this->resolveEntityClass($from->getFrom());
        }
        $joins = $this->originalQueryBuilder->getDQLPart('join');
        /* @var Expr\Join[] $childJoins */
        foreach ($joins as $rootAlias => $childJoins) {
            foreach ($childJoins as $join) {
                $result[$join->getAlias()] = $this->resolveEntityClass($join->getJoin());
            }
        }

        return $result;
    }

    /**
     * Gets the full class name if the given join expression represents an entity name in form "bundle:class"
     *
     * @param string $joinExpr
     *
     * @return string
     */
    protected function resolveEntityClass($joinExpr)
    {
        $parts = explode(':', $joinExpr);
        if (count($parts) !== 2) {
            return $joinExpr;
        }

        return $this->originalQueryBuilder
            ->getEntityManager()
            ->getConfiguration()
            ->getEntityNamespace($parts[0]) . '\\' . $parts[1];
    }

    /**
     * @param string   $alias
     * @param string[] $existingAliases
     *
     * @return \InvalidArgumentException
     */
    protected function createUnknownAliasException($alias, $existingAliases)
    {
        return new \InvalidArgumentException(
            sprintf(
                'The join alias "%s" was not found. Existing aliases: %s.',
                $alias,
                implode(', ', $existingAliases)
            )
        );
    }
}
