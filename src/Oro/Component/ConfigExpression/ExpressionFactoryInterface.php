<?php

namespace Oro\Component\ConfigExpression;

/**
 * Defines the contract for creating expression instances from registered types.
 *
 * The expression factory is responsible for instantiating expressions by name and initializing
 * them with provided options. It manages the registration of expression types through extensions
 * and ensures that created expressions are properly configured before being returned.
 */
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
