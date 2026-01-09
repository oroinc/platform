<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Defines the contract for all action implementations.
 *
 * Actions are executable units that perform operations within a workflow or automation context.
 * Each action must support initialization with configuration options, execution within a context,
 * and optional condition evaluation before execution.
 */
interface ActionInterface
{
    /**
     * Execute action.
     *
     * @param mixed $context
     */
    public function execute($context);

    /**
     * Initialize action based on passed options.
     *
     * @param array $options
     * @return ActionInterface
     * @throws InvalidParameterException
     */
    public function initialize(array $options);

    /**
     * Set optional condition for action
     *
     * @param ExpressionInterface $condition
     * @return mixed
     */
    public function setCondition(ExpressionInterface $condition);
}
