<?php

namespace Oro\Bundle\FilterBundle\Datasource\Orm;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;

class OrmExpressionBuilder implements ExpressionBuilderInterface
{
    protected $expr;

    public function __construct(Expr $expr)
    {
        $this->expr = $expr;
    }

    /**
     * {@inheritdoc}
     */
    public function andX($_)
    {
        return call_user_func_array([$this->expr, 'andX'], func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function orX($_)
    {
        return call_user_func_array([$this->expr, 'orX'], func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function comparison($x, $operator, $y, $withParam = false)
    {
        return new Expr\Comparison($x, $operator, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function eq($x, $y, $withParam = false)
    {
        return $this->expr->eq($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function neq($x, $y, $withParam = false)
    {
        /*
         * TODO: the correct expression cannot be used due a bud described in
         * http://www.doctrine-project.org/jira/browse/DDC-1858
         * It could be uncommented when doctrine version >= 2.4
         * When it uncommented you can check that all works ok, for example, on edit business unit page,
         * just try to apply 'no' filter on users grid on this page
         *
        return $this->expr->orX(
            $this->isNull($x),
            $this->expr->neq($x, $withParam ? ':' . $y : $y)
        );
        */

        return $this->expr->neq($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function lt($x, $y, $withParam = false)
    {
        return $this->expr->lt($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function lte($x, $y, $withParam = false)
    {
        return $this->expr->lte($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function gt($x, $y, $withParam = false)
    {
        return $this->expr->gt($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function gte($x, $y, $withParam = false)
    {
        return $this->expr->gte($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function not($restriction)
    {
        return $this->expr->not($restriction);
    }

    /**
     * {@inheritdoc}
     */
    public function in($x, $y, $withParam = false)
    {
        return $this->expr->in($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function notIn($x, $y, $withParam = false)
    {
        return $this->expr->orX(
            $this->isNull($x),
            $this->expr->notIn($x, $withParam ? ':' . $y : $y)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isNull($x)
    {
        return $this->expr->isNull($x);
    }

    /**
     * {@inheritdoc}
     */
    public function isNotNull($x)
    {
        return $this->expr->isNotNull($x);
    }

    /**
     * {@inheritdoc}
     */
    public function like($x, $y, $withParam = false)
    {
        return $this->expr->like($x, $withParam ? ':' . $y : $y);
    }

    /**
     * {@inheritdoc}
     */
    public function notLike($x, $y, $withParam = false)
    {
        /*
         * TODO: the correct expression cannot be used due a workaround
         * for http://www.doctrine-project.org/jira/browse/DDC-1858 implemented in
         * Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter
         * It could be uncommented when doctrine version >= 2.4 and the workaround removed
         *
         * Also we cannot use NOT (x LIKE y) due a bug in AclHelper, so we have to use NOT LIKE operator.
         * Here is the error: Notice: Undefined property: Doctrine\ORM\Query\AST\ConditionalFactor::$conditionalTerms
         *      in C:\www\home\oro\crm-dev\src\Oro\src\Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper.php on line 119
         * The problem can be reproduced, for example, on System > Data Audit grid, just try to apply
         * 'does not contain' filer to 'author' column
         * Make sure that NOT (x LIKE y) works before use it; otherwise, use NOT LIKE
         *
        return $this->expr->orX(
            $this->isNull($x),
            $this->expr->not(
                $this->expr->like($x, $withParam ? ':' . $y : $y)
            )
        );
        */

        return new Expr\Comparison($x, 'NOT LIKE', $withParam ? ':' . $y : $y);
    }
}
