<?php

namespace Oro\Bundle\FilterBundle\Datasource;

/**
 * Provides an interface for a data source restriction expressions generators
 */
interface ExpressionBuilderInterface
{
    /**
     * Creates a conjunction of the given boolean expressions.
     *
     * @param mixed $_ Expressions
     * @return mixed
     */
    public function andX($_);

    /**
     * Creates a disjunction of the given boolean expressions.
     *
     * @param mixed $_ Expressions
     * @return mixed
     */
    public function orX($_);

    /**
     * Creates an comparison expression with the given arguments.
     *
     * @param mixed  $x         Left expression
     * @param string $operator  Comparison operator
     * @param mixed  $y         Right expression
     * @param bool   $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function comparison($x, $operator, $y, $withParam = false);

    /**
     * Creates an equality comparison expression with the given arguments.
     *
     * @param mixed $x         Left expression
     * @param mixed $y         Right expression
     * @param bool  $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function eq($x, $y, $withParam = false);

    /**
     * Creates an "!=" comparison expression with the given arguments.
     *
     * @param mixed $x         Left expression
     * @param mixed $y         Right expression
     * @param bool  $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function neq($x, $y, $withParam = false);

    /**
     * Creates an "<" comparison expression with the given arguments.
     *
     * @param mixed $x         Left expression
     * @param mixed $y         Right expression
     * @param bool  $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function lt($x, $y, $withParam = false);

    /**
     * Creates an "<=" comparison expression with the given arguments.
     *
     * @param mixed $x         Left expression
     * @param mixed $y         Right expression
     * @param bool  $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function lte($x, $y, $withParam = false);

    /**
     * Creates an ">" comparison expression with the given arguments.
     *
     * @param mixed $x         Left expression
     * @param mixed $y         Right expression
     * @param bool  $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function gt($x, $y, $withParam = false);

    /**
     * Creates an ">=" comparison expression with the given arguments.
     *
     * @param mixed $x         Left expression
     * @param mixed $y         Right expression
     * @param bool  $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function gte($x, $y, $withParam = false);

    /**
     * Creates a negation expression of the given restriction.
     *
     * @param mixed $restriction Restriction to be used in NOT() function.
     * @return mixed
     */
    public function not($restriction);

    /**
     * Creates an IN() expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by IN() function
     * @param mixed  $y Argument to be used in IN() function.
     * @param bool   $withParam Indicates whether the argument to be used in IN() function is a parameter name
     * @return mixed
     */
    public function in($x, $y, $withParam = false);

    /**
     * Creates a NOT IN() expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by NOT IN() function
     * @param mixed  $y Argument to be used in NOT IN() function.
     * @param bool   $withParam Indicates whether the argument to be used in NOT IN() function is a parameter name
     * @return mixed
     */
    public function notIn($x, $y, $withParam = false);

    /**
     * Creates an IS NULL expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by IS NULL
     * @return mixed
     */
    public function isNull($x);

    /**
     * Creates an IS NOT NULL expression with the given arguments.
     *
     * @param string $x Field in string format to be restricted by IS NOT NULL
     * @return mixed
     */
    public function isNotNull($x);

    /**
     * Creates a LIKE() comparison expression with the given arguments.
     *
     * @param string $x         Field in string format to be inspected by LIKE() comparison.
     * @param mixed  $y         Argument to be used in LIKE() comparison.
     * @param bool   $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function like($x, $y, $withParam = false);

    /**
     * Creates a NOT LIKE() comparison expression with the given arguments.
     *
     * @param string $x         Field in string format to be inspected by NOT LIKE() comparison.
     * @param mixed  $y         Argument to be used in NOT LIKE() comparison.
     * @param bool   $withParam Indicates whether the right expression is a parameter name
     * @return mixed
     */
    public function notLike($x, $y, $withParam = false);

    /**
     * Creates a literal expression of the given argument.
     *
     * @param mixed $literal Argument to be converted to literal.
     *
     * @return mixed
     */
    public function literal($literal);

    /**
     * Creates an instance of TRIM() function, with the given argument.
     *
     * @param mixed $x Argument to be used as argument of TRIM() function.
     *
     * @return mixed
     */
    public function trim($x);

    /**
     * Creates an instance of COALESCE() function, with the given argument.
     *
     * @param array $x Argument to be used as argument of COALESCE() function.
     *
     * @return mixed
     */
    public function coalesce(array $x);

    /**
     * Set case sensitive parameter
     *
     * @param bool $caseInsensitive
     */
    public function setCaseInsensitive($caseInsensitive = true);
}
