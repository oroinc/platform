<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;

class UnsetValue extends AbstractAction
{
    /**
     * @var AssignValue
     */
    protected $assignValueAction;

    /**
     * @param AssignValue $assignValueAction
     */
    public function __construct(AssignValue $assignValueAction)
    {
        $this->assignValueAction = $assignValueAction;
    }

    /**
     * {@inheritdoc}
     */
    public function executeAction($context)
    {
        $this->assignValueAction->execute($context);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!isset($options['attribute']) && isset($options[0])) {
            $options[1] = null;
        } else {
            $options['value'] = null;
        }
        $this->assignValueAction->initialize($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCondition(ExpressionInterface $condition)
    {
        $this->assignValueAction->setCondition($condition);
    }
}
