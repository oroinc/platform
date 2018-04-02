<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\SQLParserUtils;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;

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

        list($params, $types) = self::processParameterMappings($query, $parsedQuery->getParameterMappings());
        list($sql, $params, $types) = SQLParserUtils::expandListParameters($sql, $params, $types);

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
        $parser = new Query\Parser($query);

        return $parser->parse();
    }
}
