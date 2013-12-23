<?php

namespace Oro\Bundle\FilterBundle\Datasource\Orm;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

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
     * @var OrmExpressionBuilder
     */
    private $expressionBuilder;

    /**
     * Constructor
     *
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb)
    {
        $this->qb                = $qb;
        $this->expressionBuilder = null;
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
        if ($this->hasLikeRestrictionToBeFixed($restriction)) {
            $this->doAddRestriction(
                $this->replaceAliasWithFieldNameInLeftPartOfRestriction($restriction),
                $condition
            );
        } else {
            $this->doAddRestriction($restriction, $condition, $isComputed);
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
     * Returns a QueryBuilder object used to modify this data source
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * Adds a new WHERE or HAVING restriction depends on the given parameters.
     *
     * @param mixed  $restriction The restriction to add.
     * @param string $condition   The condition.
     *                            Can be FilterUtility::CONDITION_OR or FilterUtility::CONDITION_AND.
     * @param bool   $isComputed  Specifies whether the restriction should be added to the HAVING part of a query.
     */
    protected function doAddRestriction($restriction, $condition, $isComputed = false)
    {
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
     * Replaces an alias with full field name in the left part of all comparison restrictions
     *
     * TODO: this is workaround for http://www.doctrine-project.org/jira/browse/DDC-1858
     * It could be removed when doctrine version >= 2.4
     *
     * @param mixed $restriction
     * @return mixed
     */
    protected function replaceAliasWithFieldNameInLeftPartOfRestriction($restriction)
    {
        $result = null;
        if ($restriction instanceof Expr\Orx || $restriction instanceof Expr\Andx) {
            $result = [];
            foreach ($restriction->getParts() as $part) {
                $result[] = $this->replaceAliasWithFieldNameInLeftPartOfRestriction($part);
            }
            $result = $restriction instanceof Expr\Orx
                ? new Expr\Orx($result)
                : new Expr\Andx($result);
        } elseif ($restriction instanceof Expr\Func) {
            $result = [];
            foreach ($restriction->getArguments() as $arg) {
                $result[] = $this->replaceAliasWithFieldNameInLeftPartOfRestriction($arg);
            }
            $result = new Expr\Func($restriction->getName(), $result);
        } elseif ($restriction instanceof Expr\Comparison) {
            $expectedAlias = (string)$restriction->getLeftExpr();
            foreach ($this->qb->getDQLPart('select') as $selectPart) {
                foreach ($selectPart->getParts() as $part) {
                    if (preg_match("#(.*)\\s+as\\s+" . preg_quote($expectedAlias) . "#i", $part, $matches)) {
                        $result = new Expr\Comparison(
                            $matches[1],
                            $restriction->getOperator(),
                            $restriction->getRightExpr()
                        );
                        break;
                    }
                }
            }
        }

        return $result !== null
            ? $result
            : $restriction;
    }

    /**
     * Checks if the given restriction has at least one LIKE or NOT LIKE expression
     * contains an field alias at the left part
     *
     * TODO: this is workaround for http://www.doctrine-project.org/jira/browse/DDC-1858
     * It could be removed when doctrine version >= 2.4
     *
     * @param mixed $restriction
     * @return bool
     */
    protected function hasLikeRestrictionToBeFixed($restriction)
    {
        if ($restriction instanceof Expr\Orx || $restriction instanceof Expr\Andx) {
            foreach ($restriction->getParts() as $part) {
                if ($this->hasLikeRestrictionToBeFixed($part)) {
                    return true;
                }
            }
        } elseif ($restriction instanceof Expr\Func) {
            foreach ($restriction->getArguments() as $arg) {
                if ($this->hasLikeRestrictionToBeFixed($arg)) {
                    return true;
                }
            }
        } elseif ($restriction instanceof Expr\Comparison) {
            if ($restriction->getOperator() === 'LIKE' || $restriction->getOperator() === 'NOT LIKE') {
                // check if the left part is an alias
                $expectedAlias = (string)$restriction->getLeftExpr();
                foreach ($this->qb->getDQLPart('select') as $selectPart) {
                    foreach ($selectPart->getParts() as $part) {
                        if (preg_match("#(.*)\\s+as\\s+" . preg_quote($expectedAlias) . "#i", $part)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
