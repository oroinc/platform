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

    /** @var CompositeExpressionInterface[] */
    private array $compositeExpressions;
    /** @var ComparisonExpressionInterface[] */
    private array $comparisonExpressions;
    private EntityClassResolver $entityClassResolver;
    /** @var string[] */
    private array $queryAliases;
    /** @var array|null [path => join alias, ...] */
    private ?array $queryJoinMap = null;
    /** @var array|null [join alias => path, ...] */
    private ?array $queryAliasMap = null;
    private ?QueryBuilder $query = null;
    private int $subqueryCount = 0;
    /** @var Parameter[] */
    private array $parameters = [];
    private ?Expr $expressionBuilder = null;
    private ?string $fieldDataType = null;

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
        $this->queryAliasMap = array_flip($queryJoinMap);
    }

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
    public function addParameter(Parameter|string $parameter, mixed $value = null, mixed $type = null): void
    {
        if (!$parameter instanceof Parameter) {
            $parameter = $this->createParameter($parameter, $value, $type);
        }
        $this->parameters[] = $parameter;
    }

    /**
     * Creates a new instance of Parameter.
     */
    public function createParameter(string $name, mixed $value, mixed $type = null): Parameter
    {
        return new Parameter($name, $value, $type);
    }

    /**
     * Builds placeholder string for given parameter name.
     */
    public function buildPlaceholder(string $parameterName): string
    {
        return ':' . $parameterName;
    }

    /**
     * Gets a builder that can be used to create a different kind of expressions.
     */
    public function getExpressionBuilder(): Expr
    {
        if (null === $this->expressionBuilder) {
            $this->expressionBuilder = new Expr();
        }

        return $this->expressionBuilder;
    }

    /**
     * Gets the data type of a field for which the current comparison expression is being building.
     */
    public function getFieldDataType(): ?string
    {
        return $this->fieldDataType;
    }

    /**
     * Builds a subquery for the given to-many association.
     * The returned subquery is joined to an association corresponds the specified field.
     * The type of root entity of the returned subquery is always equal to a target entity
     * of an association corresponds the specified field.
     *
     * For example, if there is the following main query:
     * <code>
     * SELECT account FROM Account account JOIN account.users users
     * </code>
     *
     * The subquery for the path "account.users" will be:
     * <code>
     * SELECT users_subquery1
     * FROM User users_subquery1
     * WHERE users_subquery1 = users
     * </code>
     *
     * The subquery for the path "account.users.groups" will be:
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
     * If the last element in the path is a field, the select part of the subquery will contain it,
     * e.g., the subquery for the path "account.users.name" will be:
     * <code>
     * SELECT users_subquery1.name
     * FROM User users_subquery1
     * WHERE users_subquery1 = users
     * </code>
     *
     * The subquery for the empty path will be:
     * <code>
     * SELECT account_subquery1
     * FROM Account account_subquery1
     * WHERE account_subquery1 = account
     * </code>
     *
     * @param string|null $field             The unique name of a field corresponds an association
     *                                       for which the subquery should be created
     * @param bool        $disallowJoinUsage Whether the usage of existing join to the association itself
     *                                       is disallowed; this parameter is not used if $field equals to NULL
     *
     * @return QueryBuilder
     */
    public function createSubquery(string $field = null, bool $disallowJoinUsage = false): QueryBuilder
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

        if (!$field) {
            try {
                return $this->createSubqueryToRoot();
            } catch (\Throwable $e) {
                throw new QueryException(
                    sprintf('Cannot build subquery. Reason: %s', $e->getMessage()),
                    $e->getCode(),
                    $e
                );
            }
        }

        try {
            return $this->createSubqueryByPath($this->getSubqueryPath($field), $disallowJoinUsage);
        } catch (\Throwable $e) {
            throw new QueryException(
                sprintf('Cannot build subquery for the field "%s". Reason: %s', $field, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr): mixed
    {
        $expressionType = $expr->getType();
        if (!isset($this->compositeExpressions[$expressionType])) {
            throw new QueryException(sprintf('Unknown composite %s.', $expr->getType()));
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
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison): mixed
    {
        if (!isset($this->queryAliases[0])) {
            throw new QueryException('No aliases are set before invoking walkComparison().');
        }

        [$operator, $modifier] = array_pad(explode('/', $comparison->getOperator(), 2), 2, null);
        if (!isset($this->comparisonExpressions[$operator])) {
            throw new QueryException(sprintf('Unknown comparison operator "%s".', $operator));
        }

        $field = $this->getField($comparison->getField());
        QueryBuilderUtil::checkPath($field);

        $expression = $field;
        if ('i' === $modifier) {
            $expression = sprintf('LOWER(%s)', $expression);
        } elseif (str_starts_with($modifier, ':')) {
            $this->fieldDataType = substr($modifier, 1);
        } elseif ($modifier) {
            throw new QueryException(sprintf(
                'Unknown modifier "%s" for comparison operator "%s".',
                $modifier,
                $operator
            ));
        }

        $expr = $this->comparisonExpressions[$operator]
            ->walkComparisonExpression(
                $this,
                $field,
                $expression,
                $this->getParameterName($comparison->getField()),
                $this->walkValue($comparison->getValue())
            );
        $this->fieldDataType = null;

        return $expr;
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value): mixed
    {
        return $value->getValue();
    }

    private function getField(string $field): string
    {
        foreach ($this->queryAliases as $alias) {
            if ($field !== $alias && str_starts_with($field . '.', $alias . '.')) {
                return $field;
            }
        }

        if ($field) {
            $field = $this->getRootAlias() . '.' . $field;
        }

        return $field;
    }

    private function getParameterName(string $field): string
    {
        $result = $field
            ? str_replace('.', '_', $field)
            : $this->getRootAlias();
        foreach ($this->parameters as $parameter) {
            if ($parameter->getName() === $result) {
                $result .= '_' . \count($this->parameters);
                break;
            }
        }

        return $result;
    }

    private function getSubqueryPath(string $field): ?string
    {
        $path = $field;
        $rootPrefix = $this->getRootAlias() . '.';
        if (str_starts_with($path, $rootPrefix)) {
            $path = substr($path, \strlen($rootPrefix));
        } elseif (!isset($this->queryJoinMap[$path])) {
            $parentJoinAlias = $this->getParentJoinAlias($path);
            while ($parentJoinAlias && isset($this->queryAliasMap[$parentJoinAlias])) {
                $parentJoinPath = $this->queryAliasMap[$parentJoinAlias];
                $parentJoinAlias = $this->getParentJoinAlias($parentJoinPath);
                if ($parentJoinAlias) {
                    $path = substr($parentJoinPath, 0, \strlen($parentJoinAlias) + 1) . $path;
                }
            }
        }

        return $path;
    }

    private function getParentJoinAlias(string $path): ?string
    {
        $pos = strpos($path, '.');
        if (false === $pos) {
            return null;
        }

        return substr($path, 0, $pos);
    }

    private function getJoinedPath(string $path): ?string
    {
        while (!isset($this->queryJoinMap[$path])) {
            $lastDelimiter = strrpos($path, '.');
            if (false === $lastDelimiter) {
                return null;
            }
            $path = substr($path, 0, $lastDelimiter);
        }

        return $path;
    }

    private function createSubqueryToRoot(): QueryBuilder
    {
        $subqueryAlias = $this->generateSubqueryAlias($this->getRootAlias());
        $subquery = $this->createQueryBuilder($this->getRootEntityClass(), $subqueryAlias);
        $subquery->where($subquery->expr()->eq($subqueryAlias, $this->getRootAlias()));

        return $subquery;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function createSubqueryByPath(
        string $path,
        bool $disallowJoinUsage,
        bool $allowEndsWithField = true
    ): QueryBuilder {
        if (!$disallowJoinUsage && isset($this->queryJoinMap[$path])) {
            return $this->createSubqueryByJoin($this->queryJoinMap[$path]);
        }

        $lastDelimiter = strrpos($path, '.');
        if (false === $lastDelimiter) {
            return $this->createSubqueryByRootAssociation($path);
        }

        $sourceJoinedPath = substr($path, 0, $lastDelimiter);
        $joinedPath = $this->getJoinedPath($sourceJoinedPath);
        if (null === $joinedPath) {
            $parentAlias = $this->getRootAlias();
            $parentEntityClass = $this->getRootEntityClass();
            $associationNames = explode('.', $path);
        } else {
            $parentAlias = $this->queryJoinMap[$joinedPath];
            $parentEntityClass = $this->getEntityClass($this->getJoin($parentAlias));
            $associationNames = explode('.', substr($path, \strlen($joinedPath) + 1));
        }

        $entityClass = $parentEntityClass;
        $fieldName = null;
        $associationName = null;
        $i = 0;
        foreach ($associationNames as $currentName) {
            $i++;
            $entityMetadata = $this->getClassMetadata($entityClass);
            if ($entityMetadata->hasAssociation($currentName)) {
                $entityClass = $entityMetadata->getAssociationTargetClass($currentName);
                $associationName = $currentName;
            } elseif (!$allowEndsWithField || \count($associationNames) !== $i) {
                throw new QueryException(sprintf(
                    'The "%s" must be an association in "%s" entity.',
                    $currentName,
                    $entityClass
                ));
            } else {
                if (!$entityMetadata->hasField($currentName)) {
                    throw new QueryException(sprintf(
                        'The "%s" must be an association or a field in "%s" entity.',
                        $currentName,
                        $entityClass
                    ));
                }
                $fieldName = $currentName;
                break;
            }
        }

        if ($fieldName) {
            $subquery = $this->createSubqueryByPath($sourceJoinedPath, $disallowJoinUsage, false);
            $subquery->select(
                QueryBuilderUtil::getField(QueryBuilderUtil::getSingleRootAlias($subquery), $fieldName)
            );
        } else {
            $subqueryAlias = $this->generateSubqueryAlias($associationName);
            $innerSubquery = $this->createInnerSubquery(
                $parentEntityClass,
                $this->generateSubqueryAlias($parentAlias),
                $associationNames,
                $parentAlias
            );
            $subquery = $this->createQueryBuilder($entityClass, $subqueryAlias);
            $subquery->where($subquery->expr()->in($subqueryAlias, $innerSubquery->getDQL()));
        }

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
            $query->innerJoin(sprintf('%s.%s', $parentJoinAlias, $associationName), $joinAlias);
            $parentJoinAlias = $joinAlias;
        }
        $query->select($parentJoinAlias);
        $query->where($query->expr()->eq($alias, $parentAlias));

        return $query;
    }

    private function createSubqueryByRootAssociation(string $associationName): QueryBuilder
    {
        $subqueryEntityClass = $this->getClassMetadata($this->getRootEntityClass())
            ->getAssociationTargetClass($associationName);
        $subqueryAlias = $this->generateSubqueryAlias($associationName);
        $subquery = $this->createQueryBuilder($subqueryEntityClass, $subqueryAlias);
        $subquery->where(
            $subquery->expr()->isMemberOf($subqueryAlias, sprintf('%s.%s', $this->getRootAlias(), $associationName))
        );

        return $subquery;
    }

    private function createSubqueryByJoin(string $joinAlias): QueryBuilder
    {
        $join = $this->getJoin($joinAlias);
        $subqueryEntityClass = $this->getEntityClass($join);
        $subqueryAlias = $this->generateSubqueryAlias($join->getAlias());
        $subquery = $this->createQueryBuilder($subqueryEntityClass, $subqueryAlias);
        $subquery->where($subquery->expr()->eq($subqueryAlias, $join->getAlias()));

        return $subquery;
    }

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

        throw new QueryException(sprintf('The join "%s" does not exist in the query.', $joinAlias));
    }

    private function getEntityClass(Expr\Join $join): string
    {
        $joinExpr = $join->getJoin();
        $pos = strpos($joinExpr, '.');
        if (false === $pos) {
            return $this->resolveEntityClass($joinExpr);
        }

        $parentAlias = substr($joinExpr, 0, $pos);
        $parentEntityClass = $this->getRootAlias() === $parentAlias
            ? $this->getRootEntityClass()
            : $this->getEntityClass($this->getJoin($parentAlias));

        return $this->getClassMetadata($parentEntityClass)
            ->getAssociationTargetClass(substr($joinExpr, $pos + 1));
    }

    private function getRootAlias(): string
    {
        return $this->queryAliases[0];
    }

    private function getRootEntityClass(): string
    {
        return $this->resolveEntityClass($this->query->getRootEntities()[0]);
    }

    private function generateSubqueryAlias(string $alias): string
    {
        $this->subqueryCount++;

        return sprintf(self::SUBQUERY_ALIAS_TEMPLATE, $alias, $this->subqueryCount);
    }

    private function buildSubqueryJoinAlias(string $fieldName, int $joinIndex): string
    {
        return sprintf(
            self::SUBQUERY_ALIAS_TEMPLATE,
            sprintf('%s_%d', $fieldName, $joinIndex),
            $this->subqueryCount
        );
    }

    private function createQueryBuilder(string $entityClass, string $alias): QueryBuilder
    {
        return $this->query->getEntityManager()->createQueryBuilder()
            ->from($entityClass, $alias)
            ->select($alias);
    }

    private function getClassMetadata(string $entityClass): ClassMetadata
    {
        return $this->query->getEntityManager()->getClassMetadata($entityClass);
    }

    private function resolveEntityClass(string $entityName): string
    {
        return $this->entityClassResolver->getEntityClass($entityName);
    }
}
