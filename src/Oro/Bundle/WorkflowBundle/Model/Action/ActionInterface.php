<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

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
