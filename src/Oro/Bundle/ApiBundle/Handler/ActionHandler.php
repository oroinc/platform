<?php

namespace Oro\Bundle\ApiBundle\Handler;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Bundle\ApiBundle\Processor\Context;

class ActionHandler
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
     * Creates the Context object that should be used to handling the given action type.
     *
     * @param string $action
     *
     * @return Context
     */
    public function createContext($action)
    {
        /** @var Context $context */
        $context = $this->getProcessor($action)->createContext();
        $context->setAction($action);

        return $context;
    }

    /**
     * Handles an action.
     * To create the Context object use {@see createContext} method.
     *
     * @param Context $context
     */
    public function handle(Context $context)
    {
        $this->getProcessor($context->getAction())->process($context);
    }

    /**
     * @param string $action
     *
     * @return ActionProcessor
     */
    protected function getProcessor($action)
    {
        if (!isset($this->processors[$action])) {
            throw new \RuntimeException(sprintf('The action "%s" is not defined.', $action));
        }

        return $this->processors[$action];
    }
}
