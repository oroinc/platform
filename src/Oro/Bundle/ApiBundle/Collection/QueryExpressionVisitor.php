<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\ComparisonExpressionInterface;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression\CompositeExpressionInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * This expression visitor was created to be able to add custom composite and comparison expressions.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class QueryExpressionVisitor extends ExpressionVisitor
{
    private const SUBQUERY_ALIAS_TEMPLATE = '%s_subquery%d';

    /** @var string[] */
    private $queryAliases;

    /** @var array [path => join alias, ...] */
    private $queryJoinMap;

    /** @var array [join alias => path, ...] */
    private $queryAliasMap;

    /** @var QueryBuilder */
    private $query;

    /** @var int */
    private $subqueryCount = 0;

    /** @var Parameter[] */
    private $parameters = [];

    /** @var Expr */
    private $expressionBuilder;

    /** @var CompositeExpressionInterface[] */
    private $compositeExpressions = [];

    /** @var ComparisonExpressionInterface[] */
    private $comparisonExpressions = [];

    /** @var EntityClassResolver */
    private $entityClassResolver;

    /**
     * @param CompositeExpressionInterface[]  $compositeExpressions  [type => expression, ...]
     * @param ComparisonExpressionInterface[] $comparisonExpressions [operator => expression, ...]
     * @param EntityClassResolver             $entityClassResolver
     */
    public function __construct(
        array $compositeExpressions,
        array $comparisonExpressions,
        EntityClassResolver $entityClassResolver
    ) {
        $this->compositeExpressions = $compositeExpressions;
        $this->comparisonExpressions = $comparisonExpressions;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * @param string[] $queryAliases
     */
    public function setQueryAliases(array $queryAliases): void
    {
        $this->queryAliases = $queryAliases;
    }

    /**
     * @param array $queryJoinMap [path => join alias, ...]
     */
    public function setQueryJoinMap(array $queryJoinMap): void
    {
        $this->queryJoinMap = $queryJoinMap;
        $this->queryAliasMap = \array_flip($queryJoinMap);
    }

    /**
     * @param QueryBuilder $query
     */
    public function setQuery(QueryBuilder $query): void
    {
        $this->query = $query;
    }

    /**
     * Gets bound parameters.
     *
     * @return Parameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Binds a new parameter.
     *
     * @param Parameter|string $parameter An instance of Parameter object or the name of a parameter
     * @param mixed            $value     The value of a parameter
     * @param mixed            $type      The data type of a parameter
     */
    public function addParameter($parameter, $value = null, $type = null)
    {
        if (!$parameter instanceof Parameter) {
            $parameter = $this->createParameter($parameter, $value, $type);
        }
        $this->parameters[] = $parameter;
    }

    /**
     * Creates a new instance of Parameter.
     *
     * @param string $name
     * @param mixed  $value
     * @param mixed  $type
     *
     * @return Parameter
     */
    public function createParameter(string $name, $value, $type = null): Parameter
    {
        return new Parameter($name, $value, $type);
    }

    /**
     * Builds placeholder string for given parameter name.
     *
     * @param string $parameterName
     *
     * @return string
     */
    public function buildPlaceholder(string $parameterName): string
    {
        return ':' . $parameterName;
    }

    /**
     * Gets a builder that can be used to create a different kind of expressions.
     *
     * @return Expr
     */
    public function getExpressionBuilder(): Expr
    {
        if (null === $this->expressionBuilder) {
            $this->expressionBuilder = new Expr();
        }

        return $this->expressionBuilder;
    }

    /**
     * Builds a subquery for the given to-many association.
     * The returned subquery is joined to an association corresponding the specified field.
     * The type of root entity of the returned subquery is always equal to a target entity
     * of an association corresponding the specified field.
     * For example, if there is the following main query:
     * <code>
     * SELECT a FROM Account a JOIN a.users users
     * </code>
     * The subquery for the path "a.users" will be:
     * <code>
     * SELECT users_subquery1
     * FROM User users_subquery1
     * WHERE users_subquery1 = users
     * </code>
     * The subquery for the path "a.users.groups" will be:
     * <code>
     * SELECT groups_subquery1
     * FROM Group groups_subquery1
     * WHERE groups_subquery1 IN (
     *   SELECT groups_0_subquery2
     *   FROM User users_subquery2
     *   INNER JOIN users_subquery2.groups groups_0_subquery2
     *   WHERE users_subquery2 = users
     * )
     * </code>
     *
     * @param string $field             The unique name of a field corresponds an association
     *                                  for which the subquery should be created
     * @param bool   $disallowJoinUsage Whether the usage of existing join to the association itself is disallowed
     *
     * @return QueryBuilder
     */
    public function createSubquery(string $field, bool $disallowJoinUsage = false): QueryBuilder
    {
        if (null === $this->query) {
            throw new QueryException('No query is set before invoking createSubquery().');
        }
        if (null === $this->queryJoinMap) {
            throw new QueryException('No join map is set before invoking createSubquery().');
        }
        if (!isset($this->queryAliases[0])) {
            throw new QueryException('No aliases are set before invoking createSubquery().');
        }

        try {
            return $this->createSubqueryByPath($this->getSubqueryPath($field), $disallowJoinUsage);
        } catch (\Throwable $e) {
            throw new QueryException(\sprintf(
                'Cannot build subquery for the field "%s". Reason: %s',
                $field,
                $e->getMessage()
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionType = $expr->getType();
        if (!isset($this->compositeExpressions[$expressionType])) {
            throw new QueryException(\sprintf('Unknown composite %s.', $expr->getType()));
        }

        $processedExpressions = [];
        $expressions = $expr->getExpressionList();
        foreach ($expressions as $expression) {
            $processedExpressions[] = $this->dispatch($expression);
        }

        return $this->compositeExpressions[$expressionType]
            ->walkCompositeExpression($processedExpressions);
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        if (!isset($this->queryAliases[0])) {
            throw new QueryException('No aliases are set before invoking walkComparison().');
        }

        list($operator, $modifier) = \array_pad(\explode('/', $comparison->getOperator(), 2), 2, null);
        if (!isset($this->comparisonExpressions[$operator])) {
            throw new QueryException(\sprintf('Unknown comparison operator "%s".', $operator));
        }

        $field = $this->getField($comparison->getField());
        QueryBuilderUtil::checkPath($field);

        $expression = $field;
        if ('i' === $modifier) {
            $expression = \sprintf('LOWER(%s)', $expression);
        } elseif ($modifier) {
            throw new QueryException(\sprintf(
                'Unknown modifier "%s" for comparison operator "%s".',
                $modifier,
                $operator
            ));
        }

        return $this->comparisonExpressions[$operator]
            ->walkComparisonExpression(
                $this,
                $field,
                $expression,
                $this->getParameterName($comparison->getField()),
                $this->walkValue($comparison->getValue())
            );
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    /**
     * @param string $field
     *
     * @return string
     */
    private function getField(string $field): string
    {
        foreach ($this->queryAliases as $alias) {
            if ($field !== $alias && 0 === \strpos($field . '.', $alias . '.')) {
                return $field;
            }
        }

        return $this->getRootAlias() . '.' . $field;
    }

    /**
     * @param string $field
     *
     * @return string
     */
    private function getParameterName(string $field): string
    {
        $result = \str_replace('.', '_', $field);
        foreach ($this->parameters as $parameter) {
            if ($parameter->getName() === $result) {
                $result .= '_' . \count($this->parameters);
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $field
     *
     * @return string
     */
    private function getSubqueryPath(string $field): ?string
    {
        $path = $field;
        $rootPrefix = $this->getRootAlias() . '.';
        if (0 === \strpos($path, $rootPrefix)) {
            $path = \substr($path, \strlen($rootPrefix));
        } elseif (!isset($this->queryJoinMap[$path])) {
            $parentJoinAlias = $this->getParentJoinAlias($path);
            while ($parentJoinAlias && isset($this->queryAliasMap[$parentJoinAlias])) {
                $parentJoinPath = $this->queryAliasMap[$parentJoinAlias];
                $parentJoinAlias = $this->getParentJoinAlias($parentJoinPath);
                if ($parentJoinAlias) {
                    $path = \substr($parentJoinPath, 0, \strlen($parentJoinAlias) + 1) . $path;
                }
            }
        }

        return $path;
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    private function getParentJoinAlias(string $path): ?string
    {
        $pos = \strpos($path, '.');
        if (false === $pos) {
            return null;
        }

        return \substr($path, 0, $pos);
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    private function getJoinedPath(string $path): ?string
    {
        while (!isset($this->queryJoinMap[$path])) {
            $lastDelimiter = \strrpos($path, '.');
            if (false === $lastDelimiter) {
                return null;
            }
            $path = \substr($path, 0, $lastDelimiter);
        }

        return $path;
    }

    /**
     * @param string $path
     * @param bool   $disallowJoinUsage
     *
     * @return QueryBuilder
     */
    private function createSubqueryByPath(string $path, bool $disallowJoinUsage): QueryBuilder
    {
        if (!$disallowJoinUsage && isset($this->queryJoinMap[$path])) {
            return $this->createSubqueryByJoin($this->queryJoinMap[$path]);
        }

        $lastDelimiter = \strrpos($path, '.');
        if (false === $lastDelimiter) {
            return $this->createSubqueryByRootAssociation($path);
        }

        $joinedPath = $this->getJoinedPath(\substr($path, 0, $lastDelimiter));
        if (null === $joinedPath) {
            $parentAlias = $this->getRootAlias();
            $parentEntityClass = $this->getRootEntityClass();
            $associationNames = \explode('.', $path);
        } else {
            $parentAlias = $this->queryJoinMap[$joinedPath];
            $parentEntityClass = $this->getEntityClass($this->getJoin($parentAlias));
            $associationNames = \explode('.', \substr($path, \strlen($joinedPath) + 1));
        }

        $entityClass = $parentEntityClass;
        foreach ($associationNames as $associationName) {
            $entityClass = $this->getClassMetadata($entityClass)
                ->getAssociationTargetClass($associationName);
        }
        $subqueryEntityClass = $entityClass;
        $subqueryAlias = $this->generateSubqueryAlias($associationName);

        $innerSubquery = $this->createInnerSubquery(
            $parentEntityClass,
            $this->generateSubqueryAlias($parentAlias),
            $associationNames,
            $parentAlias
        );

        $subquery = $this->createQueryBuilder($subqueryEntityClass, $subqueryAlias);
        $subquery->where($subquery->expr()->in($subqueryAlias, $innerSubquery->getDQL()));

        return $subquery;
    }

    /**
     * @param string   $entityClass
     * @param string   $alias
     * @param string[] $associationNames
     * @param string   $parentAlias
     *
     * @return QueryBuilder
     */
    private function createInnerSubquery(
        string $entityClass,
        string $alias,
        array $associationNames,
        string $parentAlias
    ): QueryBuilder {
        $query = $this->createQueryBuilder($entityClass, $alias);
        $parentJoinAlias = $alias;
        foreach ($associationNames as $index => $associationName) {
            $joinAlias = $this->buildSubqueryJoinAlias($associationName, $index);
            $query->innerJoin(\sprintf('%s.%s', $parentJoinAlias, $associationName), $joinAlias);
            $parentJoinAlias = $joinAlias;
        }
        $query->select($parentJoinAlias);
        $query->where($query->expr()->eq($alias, $parentAlias));

        return $query;
    }

    /**
     * @param string $associationName
     *
     * @return QueryBuilder
     */
    private function createSubqueryByRootAssociation(string $associationName): QueryBuilder
    {
        $subqueryEntityClass = $this->getClassMetadata($this->getRootEntityClass())
            ->getAssociationTargetClass($associationName);
        $subqueryAlias = $this->generateSubqueryAlias($associationName);
        $subquery = $this->createQueryBuilder($subqueryEntityClass, $subqueryAlias);
        $subquery->where(
            $subquery->expr()->isMemberOf($subqueryAlias, \sprintf('%s.%s', $this->getRootAlias(), $associationName))
        );

        return $subquery;
    }

    /**
     * @param string $joinAlias
     *
     * @return QueryBuilder
     */
    private function createSubqueryByJoin(string $joinAlias): QueryBuilder
    {
        $join = $this->getJoin($joinAlias);
        $subqueryEntityClass = $this->getEntityClass($join);
        $subqueryAlias = $this->generateSubqueryAlias($join->getAlias());
        $subquery = $this->createQueryBuilder($subqueryEntityClass, $subqueryAlias);
        $subquery->where($subquery->expr()->eq($subqueryAlias, $join->getAlias()));

        return $subquery;
    }

    /**
     * @param string $joinAlias
     *
     * @return Expr\Join
     */
    private function getJoin(string $joinAlias): Expr\Join
    {
        $rootAlias = $this->getRootAlias();
        $joins = $this->query->getDQLPart('join');
        if (isset($joins[$rootAlias])) {
            /** @var Expr\Join $join */
            foreach ($joins[$rootAlias] as $join) {
                if ($join->getAlias() === $joinAlias) {
                    return $join;
                }
            }
        }

        throw new QueryException(\sprintf('The join "%s" does not exist in the query.', $joinAlias));
    }

    /**
     * @param Expr\Join $join
     *
     * @return string
     */
    private function getEntityClass(Expr\Join $join): string
    {
        $joinExpr = $join->getJoin();
        $pos = \strpos($joinExpr, '.');
        if (false === $pos) {
            return $this->resolveEntityClass($joinExpr);
        }

        $parentEntityClass = null;
        $parentAlias = \substr($joinExpr, 0, $pos);
        $parentEntityClass = $this->getRootAlias() === $parentAlias
            ? $this->getRootEntityClass()
            : $this->getEntityClass($this->getJoin($parentAlias));

        return $this->getClassMetadata($parentEntityClass)
            ->getAssociationTargetClass(\substr($joinExpr, $pos + 1));
    }

    /**
     * @return string
     */
    private function getRootAlias(): string
    {
        return $this->queryAliases[0];
    }

    /**
     * @return string
     */
    private function getRootEntityClass(): string
    {
        return $this->resolveEntityClass($this->query->getRootEntities()[0]);
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    private function generateSubqueryAlias(string $alias): string
    {
        $this->subqueryCount++;

        return \sprintf(self::SUBQUERY_ALIAS_TEMPLATE, $alias, $this->subqueryCount);
    }

    /**
     * @param string $fieldName
     * @param int    $joinIndex
     *
     * @return string
     */
    private function buildSubqueryJoinAlias(string $fieldName, int $joinIndex): string
    {
        return \sprintf(
            self::SUBQUERY_ALIAS_TEMPLATE,
            \sprintf('%s_%d', $fieldName, $joinIndex),
            $this->subqueryCount
        );
    }

    /**
     * @param string $entityClass
     * @param string $alias
     *
     * @return QueryBuilder
     */
    private function createQueryBuilder(string $entityClass, string $alias): QueryBuilder
    {
        return $this->query->getEntityManager()->createQueryBuilder()
            ->from($entityClass, $alias)
            ->select($alias);
    }

    /**
     * @param string $entityClass
     *
     * @return ClassMetadata
     */
    private function getClassMetadata(string $entityClass): ClassMetadata
    {
        return $this->query->getEntityManager()->getClassMetadata($entityClass);
    }

    /**
     * @param string $entityName
     *
     * @return string
     */
    private function resolveEntityClass(string $entityName): string
    {
        return $this->entityClassResolver->getEntityClass($entityName);
    }
}
