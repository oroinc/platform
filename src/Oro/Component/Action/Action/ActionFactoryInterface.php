<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;

interface ActionFactoryInterface extends FactoryWithTypesInterface
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
