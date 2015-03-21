<?php

namespace Oro\Bundle\LayoutBundle\Layout\Generator;

use Oro\Component\ConfigExpression\ExpressionFactoryInterface;

/**
 * ExpressionFactoryAwareInterface should be implemented by classes that depends on a expression factory.
 */
interface ExpressionFactoryAwareInterface
{
    /**
     * Sets the expression factory.
     *
     * @param ExpressionFactoryInterface $expressionFactory
     */
    public function setExpressionFactory(ExpressionFactoryInterface $expressionFactory);
}
