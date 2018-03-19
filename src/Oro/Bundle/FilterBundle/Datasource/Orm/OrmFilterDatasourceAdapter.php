<?php

namespace Oro\Bundle\FilterBundle\Datasource\Orm;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

/**
 * Represents an adapter to ORM data source
 */
class OrmFilterDatasourceAdapter implements FilterDatasourceAdapterInterface
{
    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * @var QueryBuilderTools
     */
    protected $qbTools;

    /**
     * @var OrmExpressionBuilder
     */
    private $expressionBuilder;

    /**
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
        $this->qbTools = new QueryBuilderTools($this->qb->getDQLPart('select'));

        $this->expressionBuilder = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return $this->qb->getEntityManager()->getConnection()->getDatabasePlatform();
    }

    /**
     * Adds a new WHERE or HAVING restriction depends on the given parameters.
     *
     * @param mixed  $restriction The restriction to add.
     * @param string $condition   The condition.
     *                            Can be FilterUtility::CONDITION_OR or FilterUtility::CONDITION_AND.
     * @param bool   $isComputed  Specifies whether the restriction should be added to the HAVING part of a query.
     */
    public function addRestriction($restriction, $condition, $isComputed = false)
    {
        if (!($isComputed && $this->getDatabasePlatform() instanceof MySqlPlatform)) {
            $restriction = $this->qbTools->replaceAliasesWithFields($restriction);
        }

        if ($condition === FilterUtility::CONDITION_OR) {
            if ($isComputed) {
                $this->qb->orHaving($restriction);
            } else {
                $this->qb->orWhere($restriction);
            }
        } else {
            if ($isComputed) {
                $this->qb->andHaving($restriction);
            } else {
                $this->qb->andWhere($restriction);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($_)
    {
        return call_user_func_array([$this->qb, 'groupBy'], func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy($_)
    {
        return call_user_func_array([$this->qb, 'addGroupBy'], func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function expr()
    {
        if ($this->expressionBuilder === null) {
            $this->expressionBuilder = new OrmExpressionBuilder($this->qb->expr());
        }

        return $this->expressionBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($key, $value, $type = null)
    {
        $this->qb->setParameter($key, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function generateParameterName($filterName)
    {
        return preg_replace('#[^a-z0-9]#i', '', $filterName) . mt_rand();
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldByAlias($fieldName)
    {
        return $this->qbTools->getFieldByAlias($fieldName);
    }

    /**
     * Returns a QueryBuilder object used to modify this data source
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * Creates new instance of QueryBuilder object
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->qb->getEntityManager()->createQueryBuilder();
    }
}
