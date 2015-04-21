<?php

namespace Oro\Component\ConfigExpression\Extension;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Interface for extensions which provide expression types.
 */
interface ExtensionInterface
{
    /**
     * Returns an expression by name.
     *
     * @param string $name The name of the expression
     *
     * @return ExpressionInterface
     *
     * @throws Exception\InvalidArgumentException if the given expression is not supported by this extension
     */
    public function getExpression($name);

    /**
     * Checks whether the given expression is supported.
     *
     * @param string $name The name of the expression
     *
     * @return bool true, if the given expression is supported by this extension; otherwise, false
     */
    public function hasExpression($name);
}
