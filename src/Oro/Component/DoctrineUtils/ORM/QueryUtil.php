<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\SQLParserUtils;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * Provides a set of static methods to work with ORM query.
 */
class QueryUtil
{
    /**
     * Makes full clone of the given query, including its parameters and hints
     *
     * @param Query $query
     *
     * @return Query
     */
    public static function cloneQuery(Query $query)
    {
        $cloneQuery = clone $query;

        // clone parameters
        $cloneQuery->setParameters(clone $query->getParameters());

        // clone hints
        $hints = $query->getHints();
        foreach ($hints as $name => $value) {
            $cloneQuery->setHint($name, $value);
        }

        return $cloneQuery;
    }

    /**
     * Adds a custom tree walker to the given query.
     * Do nothing if the query already has the given walker.
     *
     * @param Query  $query       The query
     * @param string $walkerClass The FQCN of the tree walker
     *
     * @return bool TRUE if the walker was added; otherwise, FALSE
     */
    public static function addTreeWalker(Query $query, $walkerClass)
    {
        $walkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);
        if (false === $walkers) {
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [$walkerClass]);

            return true;
        }
        if (!in_array($walkerClass, $walkers, true)) {
            $walkers[] = $walkerClass;
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $walkers);

            return true;
        }

        return false;
    }

    /**
     * @param Query $query
     * @param array $paramMappings
     *
     * @return array
     *
     * @throws QueryException
     */
    public static function processParameterMappings(Query $query, $paramMappings)
    {
        $sqlParams = [];
        $types     = [];

        /** @var Query\Parameter $parameter */
        foreach ($query->getParameters() as $parameter) {
            $key = $parameter->getName();

            if (!isset($paramMappings[$key])) {
                throw QueryException::unknownParameter($key);
            }

            $value = $query->processParameterValue($parameter->getValue());
            $type  = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : Query\ParameterTypeInferer::inferType($value);

            foreach ($paramMappings[$key] as $position) {
                $types[$position] = $type;
            }

            $sqlPositions = $paramMappings[$key];
            $value        = [$value];
            $countValue   = count($value);

            for ($i = 0, $l = count($sqlPositions); $i < $l; $i++) {
                $sqlParams[$sqlPositions[$i]] = $value[($i % $countValue)];
            }
        }

        if (count($sqlParams) !== count($types)) {
            throw QueryException::parameterTypeMismatch();
        }

        if ($sqlParams) {
            ksort($sqlParams);
            $sqlParams = array_values($sqlParams);

            ksort($types);
            $types = array_values($types);
        }

        return [$sqlParams, $types];
    }

    /**
     * @param Query                   $query
     * @param Query\ParserResult|null $parsedQuery
     *
     * @return string
     *
     * @throws QueryException
     */
    public static function getExecutableSql(Query $query, Query\ParserResult $parsedQuery = null)
    {
        if (null === $parsedQuery) {
            $parsedQuery = static::parseQuery($query);
        }

        $sql = $parsedQuery->getSqlExecutor()->getSqlStatements();

        [$params, $types] = self::processParameterMappings($query, $parsedQuery->getParameterMappings());
        [$sql, $params, $types] = SQLParserUtils::expandListParameters($sql, $params, $types);

        $paramPos = SQLParserUtils::getPlaceholderPositions($sql);
        for ($i = count($paramPos) - 1; $i >= 0; $i--) {
            $sql = substr_replace(
                $sql,
                $query->getEntityManager()->getConnection()->quote($params[$i], $types[$i]),
                $paramPos[$i],
                1
            );
        }

        return $sql;
    }

    /**
     * @param Query $query
     *
     * @return Query\ParserResult
     */
    public static function parseQuery(Query $query)
    {
        // we have to call the private _parse() method to be able to use the query cache
        // and as result avoid unneeded query parsing when the parse result is already cached
        $parseClosure = \Closure::bind(
            fn (Query $query) => $query->parse(),
            null,
            $query
        );

        return $parseClosure($query);
    }

    public static function resetResultSetMapping(Query $query): void
    {
        // unfortunately the reflection is the only way to reset the result-set mapping
        $resultSetMappingProperty = ReflectionUtil::getProperty(
            new \ReflectionClass($query),
            '_resultSetMapping'
        );
        if (null === $resultSetMappingProperty) {
            throw new \LogicException(sprintf(
                'The "_resultSetMapping" property does not exist in %s.',
                \get_class($query)
            ));
        }
        $resultSetMappingProperty->setAccessible(true);
        $resultSetMappingProperty->setValue($query, null);
    }

    /**
     * Removes parameters from ORM query.
     * Removes parameter mappings from {@see ParserResult} if any.
     */
    public static function resetParameters(Query $query, ParserResult $parserResult = null): void
    {
        // Removes parameters and parameter mappings.
        $query->setParameters(new ArrayCollection());

        if ($parserResult === null) {
            /** @var Query\ParserResult $parserResult */
            $parserResult = \Closure::bind(static fn (Query $query) => $query->parserResult, null, $query)($query);
        }

        if ($parserResult !== null) {
            $clearMappings = \Closure::bind(
                static function (Query\ParserResult $parserResult) {
                    $parserResult->_parameterMappings = [];
                },
                null,
                $parserResult
            );

            $clearMappings($parserResult);
        }
    }
}
