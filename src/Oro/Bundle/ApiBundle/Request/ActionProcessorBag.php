<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Component\ChainProcessor\ActionProcessor;

class ActionProcessorBag
{
    /** @var ActionProcessor[] */
    protected $processors = [];

    /**
     * Registers a processor for the given action type.
     *
     * @param string          $action
     * @param ActionProcessor $processor
     */
    public function addProcessor($action, ActionProcessor $processor)
    {
        $this->processors[$action] = $processor;
    }

    /**
     * Gets a processor responsible to handle the given action.
     *
     * @param string $action
     *
     * @return ActionProcessor
     */
    public function getProcessor($action)
    {
        if (!isset($this->processors[$action])) {
            throw new \RuntimeException(sprintf('The action "%s" is not defined.', $action));
        }

        return $this->processors[$action];
    }
}
