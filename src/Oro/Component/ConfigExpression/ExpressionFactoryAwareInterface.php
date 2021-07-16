<?php

namespace Oro\Component\ConfigExpression;

/**
 * ExpressionFactoryAwareInterface should be implemented by classes that depends on the config expression factory.
 */
interface ExpressionFactoryAwareInterface
{
    /**
     * Sets the expression factory.
     */
    public function setExpressionFactory(ExpressionFactoryInterface $expressionFactory);
}
