<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

/**
 * Represents a native SQL query.
 */
class SqlQuery extends AbstractQuery
{
    /** @var SqlQueryBuilder */
    private $qb;

    public function setSqlQueryBuilder(SqlQueryBuilder $qb)
    {
        $this->qb = $qb;
        $parameters = $qb->getParameters();
        if ($parameters) {
            foreach ($qb->getParameters() as $key => $value) {
                $this->setParameter($key, $value, $this->qb->getParameterType($key));
            }
        }
    }

    /**
     * Returns the query builder used for build this query.
     *
     * @return SqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQL()
    {
        return $this->qb->getSQL();
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    protected function _doExecute()
    {
        $parameters = [];
        $types = [];

        /** @var Query\Parameter $parameter */
        foreach ($this->getParameters() as $parameter) {
            $name = $parameter->getName();
            $value = $this->processParameterValue($parameter->getValue());
            $type = ($parameter->getValue() === $value)
                ? $parameter->getType()
                : Query\ParameterTypeInferer::inferType($value);

            $parameters[$name] = $value;
            $types[$name] = $type;
        }

        if ($parameters && is_int(key($parameters))) {
            ksort($parameters);
            ksort($types);

            $parameters = array_values($parameters);
            $types = array_values($types);
        }

        $sql = $this->qb->getSQL();
        if (preg_match('/\s*(UPDATE|DELETE|INSERT)\s+/i', $sql)) {
            return $this->_em->getConnection()->executeStatement($sql, $parameters, $types);
        }

        return $this->_em->getConnection()->executeQuery($sql, $parameters, $types, $this->_queryCacheProfile);
    }
    // @codingStandardsIgnoreEnd

    /**
     * Deep clone of all expression objects in the SQL parts.
     *
     * @return void
     */
    public function __clone()
    {
        parent::__clone();
        $this->qb = clone $this->qb;
    }
}
