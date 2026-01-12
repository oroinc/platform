<?php

namespace Oro\Component\ConfigExpression;

/**
 * Defines the contract for all expression types in the configuration expression system.
 *
 * Expressions are the core building blocks of the configuration expression system. They can be
 * evaluated against a context to produce results, converted to array or PHP code representations,
 * and initialized with options. This interface is implemented by conditions, functions, and other
 * expression types that can be used in declarative configuration.
 */
interface ExpressionInterface
{
    /**
     * Returns the expression name.
     *
     * @return string
     */
    public function getName();

    /**
     * Evaluates the expression, optionally add error(s) to the given collection.
     *
     * @param mixed             $context The evaluation context
     * @param \ArrayAccess|null $errors  The errors collection
     *
     * @return mixed
     */
    public function evaluate($context, ?\ArrayAccess $errors = null);

    /**
     * Sets the condition error message.
     *
     * @param string $message
     *
     * @return self
     */
    public function setMessage($message);

    /**
     * Initializes the condition based on passed options.
     *
     * @param array $options
     *
     * @return self
     *
     * @throws Exception\ExceptionInterface if the condition cannot be initialized from the given options
     */
    public function initialize(array $options);

    /**
     * Gets an array representation of the expression.
     *
     * @return array
     */
    public function toArray();

    /**
     * Gets PHP code representation of the expression.
     *
     * @param string $factoryAccessor A piece of PHP code to get expression factory,
     *                                for example "$expressionFactory"
     *
     * @return string
     */
    public function compile($factoryAccessor);
}
