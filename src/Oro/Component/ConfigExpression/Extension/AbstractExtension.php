<?php

namespace Oro\Component\ConfigExpression\Extension;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Provides a base implementation for expression extensions with lazy-loading of expressions.
 *
 * This abstract class implements the extension interface and handles lazy initialization of
 * expressions. Subclasses should override the {@see loadExpressions()} method to provide their
 * expression implementations. Expressions are loaded on-demand and cached for subsequent access.
 */
class AbstractExtension implements ExtensionInterface
{
    /**
     * The expressions provided by this extension
     *
     * @var ExpressionInterface[]
     *
     * Example:
     *  [
     *      'expression_1' => ExpressionInterface,
     *      'expression_2' => ExpressionInterface
     *  ]
     */
    private $expressions;

    #[\Override]
    public function getExpression($name)
    {
        if (null === $this->expressions) {
            $this->initExpressions();
        }

        if (!isset($this->expressions[$name])) {
            throw new Exception\InvalidArgumentException(
                sprintf('The expression "%s" can not be loaded by this extension.', $name)
            );
        }

        return $this->expressions[$name];
    }

    #[\Override]
    public function hasExpression($name)
    {
        if (null === $this->expressions) {
            $this->initExpressions();
        }

        return isset($this->expressions[$name]);
    }

    /**
     * Registers expressions.
     *
     * @return ExpressionInterface[]
     */
    protected function loadExpressions()
    {
        return [];
    }

    /**
     * Initializes expressions.
     *
     * @throws Exception\UnexpectedTypeException if any registered expression is not
     *                                           an instance of ExpressionInterface
     */
    private function initExpressions()
    {
        $this->expressions = [];

        foreach ($this->loadExpressions() as $expr) {
            if (!$expr instanceof ExpressionInterface) {
                throw new Exception\UnexpectedTypeException(
                    $expr,
                    'Oro\Component\ConfigExpression\ExpressionInterface'
                );
            }

            $this->expressions[$expr->getName()] = $expr;
        }
    }
}
