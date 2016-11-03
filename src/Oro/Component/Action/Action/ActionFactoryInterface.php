<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;

interface ActionFactoryInterface
{
    /**
     * Creates an action.
     *
     * @param string $type
     * @param array $options
     * @param ExpressionInterface $condition
     * @throws \RunTimeException
     * @return ActionInterface
     */
    public function create($type, array $options = array(), ExpressionInterface $condition = null);
}
