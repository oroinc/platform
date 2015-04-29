<?php

namespace Oro\Component\ConfigExpression;

interface ExpressionFactoryInterface
{
    /**
     * Creates an object responsible to handle expression of the given type.
     *
     * @param string $name    The expression name
     * @param array  $options The options
     *
     * @return ExpressionInterface
     *
     * @throws Exception\InvalidArgumentException if the expression cannot be created
     * @throws Exception\UnexpectedTypeException if the expression has been found, but its type is invalid
     */
    public function create($name, array $options = []);
}
