<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;

class UnsetValue extends AbstractAction
{
    /**
     * @var AssignValue
     */
    protected $assignValueAction;

    public function __construct(AssignValue $assignValueAction)
    {
        $this->assignValueAction = $assignValueAction;
    }

    #[\Override]
    public function executeAction($context)
    {
        $this->assignValueAction->execute($context);
    }

    #[\Override]
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

    #[\Override]
    public function setCondition(ExpressionInterface $condition)
    {
        $this->assignValueAction->setCondition($condition);
    }
}
