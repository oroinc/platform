<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;

/**
 * Defines the contract for creating action instances from configuration.
 *
 * This factory interface extends the base factory with types support, allowing
 * the creation of different action types based on configuration. Implementations
 * should handle action instantiation with optional conditions and parameters.
 */
interface ActionFactoryInterface extends FactoryWithTypesInterface
{
    /**
     * Creates an action.
     *
     * @param string $type
     * @param array $options
     * @param ExpressionInterface|null $condition
     * @throws \RunTimeException
     * @return ActionInterface
     */
    public function create($type, array $options = array(), ?ExpressionInterface $condition = null);
}
