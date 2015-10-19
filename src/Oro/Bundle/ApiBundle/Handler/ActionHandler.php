<?php

namespace Oro\Bundle\ApiBundle\Handler;

use Oro\Component\ChainProcessor\ChainProcessor;
use Oro\Bundle\ApiBundle\Processor\Context;

class ActionHandler
{
    /** @var array */
    protected $processors = [];

    /**
     * @param string         $action
     * @param ChainProcessor $processor
     * @param string         $contextClass
     */
    public function addProcessor($action, ChainProcessor $processor, $contextClass)
    {
        $this->processors[$action] = [
            'processor'    => $processor,
            'contextClass' => $contextClass
        ];
    }

    /**
     * @param string $action
     *
     * @return Context
     */
    public function createContext($action)
    {
        $this->assertActionDefined($action);

        $className = $this->processors[$action]['contextClass'];

        /** @var Context $context */
        $context = new $className();
        $context->setAction($action);

        return $context;
    }

    /**
     * @param Context $context
     */
    public function handle(Context $context)
    {
        $processor = $this->getProcessor($context->getAction());
        $processor->process($context);
    }

    /**
     * @param string $action
     *
     * @return ChainProcessor
     */
    protected function getProcessor($action)
    {
        $this->assertActionDefined($action);

        return $this->processors[$action]['processor'];
    }

    /**
     * @param string $action
     */
    protected function assertActionDefined($action)
    {
        if (!isset($this->processors[$action])) {
            throw new \RuntimeException(sprintf('The action "%s" is not defined.', $action));
        }
    }
}
