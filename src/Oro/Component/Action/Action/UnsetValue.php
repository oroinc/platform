<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Removes a value from a context attribute by setting it to null.
 *
 * This action delegates to {@see AssignValue} to set a context attribute to null, effectively unsetting
 * or clearing the value. It provides a semantic alternative to explicitly assigning null values.
 */
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
