<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * A set of reusable static methods to help building of queries.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class QueryBuilderUtil
{
    const IN         = 'in';
    const IN_BETWEEN = 'in_between';

    /**
     * Calculates the page offset
     *
     * @param int $page  The page number
     * @param int $limit The maximum number of items per page
     *
     * @return int
     */
    public static function getPageOffset($page, $limit)
    {
        return $page > 0
            ? ($page - 1) * $limit
            : 0;
    }

    /**
     * Checks the given criteria and converts them to Criteria object if needed
     *
     * @param Criteria|array|null $criteria
     *
     * @return Criteria
     */
    public static function normalizeCriteria($criteria)
    {
        if (null === $criteria) {
            $criteria = new Criteria();
        } elseif (is_array($criteria)) {
            $newCriteria = new Criteria();
            foreach ($criteria as $fieldName => $value) {
                $newCriteria->andWhere(Criteria::expr()->eq($fieldName, $value));
            }

            $criteria = $newCriteria;
        }

        return $criteria;
    }

    /**
     * Returns an expression in SELECT clause by its alias
     *
     * @param QueryBuilder $qb
     * @param string       $alias An alias of an expression in SELECT clause
     *
     * @return string|null
     */
    public static function getSelectExprByAlias(QueryBuilder $qb, $alias)
    {
        /** @var \Doctrine\ORM\Query\Expr\Select $selectPart */
        foreach ($qb->getDQLPart('select') as $selectPart) {
            foreach ($selectPart->getParts() as $part) {
                if (preg_match_all('#(\,\s*)*(?P<expr>.+?)\\s+AS\\s+(?P<alias>\\w+)#i', $part, $matches)) {
                    foreach ($matches['alias'] as $key => $val) {
                        if ($val === $alias) {
                            return $matches['expr'][$key];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Gets the root entity alias of the query.
     *
     * @param QueryBuilder $qb             The query builder
     * @param bool         $throwException Whether to throw exception in case the query does not have a root alias
     *
     * @return string|null
     *
     * @throws QueryException
     */
    public static function getSingleRootAlias(QueryBuilder $qb, $throwException = true)
    {
        $rootAliases = $qb->getRootAliases();

        $result = null;
        if (count($rootAliases) !== 1) {
            if ($throwException) {
                $errorReason = count($rootAliases) === 0
                    ? 'the query has no any root aliases'
                    : sprintf('the query has several root aliases. "%s"', implode(', ', $rootAliases));

                throw new QueryException(
                    sprintf(
                        'Can\'t get single root alias for the given query. Reason: %s.',
                        $errorReason
                    )
                );
            }
        } else {
            $result = $rootAliases[0];
        }

        return $result;
    }

    /**
     * Applies the given joins for the query builder
     *
     * @param QueryBuilder $qb
     * @param array|null   $joins
     */
    public static function applyJoins(QueryBuilder $qb, $joins)
    {
        if (empty($joins)) {
            return;
        }

        $qb->distinct(true);
        $rootAlias = self::getSingleRootAlias($qb);
        foreach ($joins as $key => $val) {
            self::checkIdentifier($key);
            if (empty($val)) {
                $qb->leftJoin($rootAlias . '.' . $key, $key);
            } elseif (is_array($val)) {
                if (isset($val['join'])) {
                    $join = $val['join'];
                    if (false === strpos($join, '.')) {
                        $join = $rootAlias . '.' . $join;
                    }
                } else {
                    $join = $rootAlias . '.' . $key;
                }
                $condition     = null;
                $conditionType = null;
                if (isset($val['condition'])) {
                    $condition     = $val['condition'];
                    $conditionType = Expr\Join::WITH;
                }
                if (isset($val['conditionType'])) {
                    $conditionType = $val['conditionType'];
                }
                $qb->leftJoin($join, $key, $conditionType, $condition);
            } else {
                self::checkIdentifier($val);
                $qb->leftJoin($rootAlias . '.' . $val, $val);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $field
     * @param int[]        $values
     */
    public static function applyOptimizedIn(QueryBuilder $qb, $field, array $values)
    {
        self::checkField($field);
        $expressions     = $qb->expr()->orX();
        $optimizedValues = self::optimizeIntegerValues($values);

        if ($optimizedValues[self::IN]) {
            $param = self::generateParameterName($field);

            $qb->setParameter($param, $optimizedValues[self::IN]);
            $expressions->add($qb->expr()->in($field, sprintf(':%s', $param)));
        }

        foreach ($optimizedValues[self::IN_BETWEEN] as $range) {
            list($min, $max) = $range;
            $minParam = self::generateParameterName($field);
            $maxParam = self::generateParameterName($field);

            $qb->setParameter($minParam, $min);
            $qb->setParameter($maxParam, $max);

            $expressions->add($qb->expr()->between($field, sprintf(':%s', $minParam), sprintf(':%s', $maxParam)));
        }

        if (count($expressions->getParts()) > 0) {
            $qb->andWhere($expressions);
        }
    }

    /**
     * @param int[] $values
     *
     * @return array
     */
    public static function optimizeIntegerValues(array $values)
    {
        $result = [
            self::IN         => [],
            self::IN_BETWEEN => [],
        ];

        $ranges = ArrayUtil::intRanges($values);
        foreach ($ranges as $range) {
            list($min, $max) = $range;
            if ($min === $max) {
                $result[self::IN][] = $min;
            } else {
                $result[self::IN_BETWEEN][] = $range;
            }
        }

        // when there is lots of ranges, it takes way longer than IN
        if (count($result[self::IN_BETWEEN]) > 1000) {
            $result[self::IN]         = $values;
            $result[self::IN_BETWEEN] = [];
        }

        return $result;
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public static function generateParameterName($prefix)
    {
        self::checkField($prefix);
        static $n = 0;
        $n++;

        return sprintf('%s_%d', uniqid(str_replace('.', '', $prefix)), $n);
    }

    /**
     * Removes unused parameters from query builder
     *
     * @param QueryBuilder $qb
     */
    public static function removeUnusedParameters(QueryBuilder $qb)
    {
        $dql = $qb->getDQL();
        $usedParameters = [];
        foreach ($qb->getParameters() as $parameter) {
            /** @var \Doctrine\ORM\Query\Parameter $parameter */
            $parameterName = $parameter->getName();
            if (DqlUtil::hasParameter($dql, $parameterName)) {
                $usedParameters[$parameterName] = $parameter->getValue();
            }
        }

        $qb->setParameters($usedParameters);
    }

    /**
     * @param QueryBuilder $qb
     * @param Expr\Join $join
     *
     * @return string
     */
    public static function getJoinClass(QueryBuilder $qb, Expr\Join $join)
    {
        if (class_exists($join->getJoin())) {
            return $join->getJoin();
        }

        $fromParts = $qb->getDqlPart('from');
        $aliasToClassMap = [];
        foreach ($fromParts as $from) {
            $aliasToClassMap[$from->getAlias()] = $from->getFrom();
        }

        list($parentAlias, $field) = explode('.', $join->getJoin());
        $parentClass = $aliasToClassMap[$parentAlias]
            ?? self::getJoinClass($qb, self::findJoinByAlias($qb, $parentAlias));

        return $qb->getEntityManager()
            ->getClassMetadata($parentClass)
            ->getAssociationTargetClass($field);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     *
     * @return Expr\Join|null
     */
    public static function findJoinByAlias(QueryBuilder $qb, $alias)
    {
        $joinParts = $qb->getDQLPart('join');
        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $alias) {
                    return $join;
                }
            }
        }

        return null;
    }

    /**
     * Checks whether an entity with the given alias is associated with a root entity by to-one relationship.
     * If the checked entity is associated with the root entity indirectly this method returns true only if
     * all intermediate associations are represented by to-one relationships as well.
     *
     * @param QueryBuilder $qb
     * @param string $alias
     *
     * @return bool
     */
    public static function isToOne(QueryBuilder $qb, $alias)
    {
        $rootAliases = $qb->getRootAliases();
        if (count($rootAliases) !== 1) {
            return false;
        }

        $joins = [];
        $currentAlias = $alias;
        while ($parentAliasWithJoin = self::findParentAliasWithJoin($qb, $currentAlias)) {
            $currentAlias = key($parentAliasWithJoin);
            $joins[] = reset($parentAliasWithJoin);
        }

        if ($currentAlias !== $rootAliases[0]) {
            return false;
        }

        $rootEntities = $qb->getRootEntities();
        $em = $qb->getEntityManager();

        $metadata = $em->getClassMetadata($rootEntities[0]);
        $startIndex = count($joins) - 1;
        for ($i = $startIndex; $i >= 0; $i--) {
            list(, $field) = explode('.', $joins[$i]);
            if (!isset($metadata->associationMappings[$field])) {
                return false;
            }

            $assoc = $metadata->associationMappings[$field];

            if (!($assoc['type'] & ClassMetadataInfo::TO_ONE)) {
                return false;
            }

            $metadata = $em->getClassMetadata($assoc['targetEntity']);
        }

        return true;
    }

    /**
     * Query safe replacement for sprintf
     *
     * @param string $format
     * @param array ...$args
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function sprintf($format, ...$args): string
    {
        foreach ($args as $arg) {
            self::checkField($arg);
        }

        return sprintf($format, ...$args);
    }

    /**
     * Check that passed identifier is safe for usage in dynamic DQL
     *
     * @param string $str
     * @throws \InvalidArgumentException
     */
    public static function checkIdentifier($str)
    {
        if (preg_match('/[\W]+/', $str)) {
            throw new \InvalidArgumentException(sprintf('Unsafe value passed %s', $str));
        }
    }

    /**
     * Check that passed field is safe for usage in dynamic DQL. Field may be in format (?alias.)field
     *
     * @param string $str
     * @throws \InvalidArgumentException
     */
    public static function checkField($str)
    {
        if (strpos($str, '.') !== false) {
            list($alias, $field) = explode('.', $str, 2);
            self::checkIdentifier($alias);
            self::checkIdentifier($field);
        } else {
            self::checkIdentifier($str);
        }
    }

    /**
     * Check that passed path is safe for usage in dynamic DQL. Path may be in format (?alias.)field(.?field)...
     *
     * @param string $str
     * @throws \InvalidArgumentException
     */
    public static function checkPath($str)
    {
        $items = explode('.', $str);
        foreach ($items as $item) {
            self::checkIdentifier($item);
        }
    }

    /**
     * Check that passed parameter is safe for usage in dynamic DQL
     *
     * @param string $str
     * @throws \InvalidArgumentException
     */
    public static function checkParameter($str)
    {
        if (strpos($str, ':') === 0) {
            self::checkIdentifier(substr($str, 1));
        }
    }

    /**
     * Construct safe identifier field name based table alias and field name.
     *
     * @param string $alias
     * @param string $field
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getField($alias, $field): string
    {
        return self::sprintf('%s.%s', $alias, $field);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     *
     * @return array|null [parent alias, join]
     */
    private static function findParentAliasWithJoin(QueryBuilder $qb, $alias)
    {
        $joinPart = $qb->getDQLPart('join');
        foreach ($joinPart as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $alias) {
                    list($parentAlias) = explode('.', $join->getJoin());

                    return [$parentAlias => $join->getJoin()];
                }
            }
        }

        return null;
    }

    /**
     * @param string $sortOrder
     * @return string
     */
    public static function getSortOrder($sortOrder)
    {
        if (\strtolower($sortOrder) === 'asc') {
            return 'ASC';
        }
        if (\strtolower($sortOrder) === 'desc') {
            return 'DESC';
        }

        throw new \InvalidArgumentException(sprintf('Unsafe value passed %s', $sortOrder));
    }
}
