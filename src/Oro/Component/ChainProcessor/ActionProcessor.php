<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base class for action processors.
 */
class ActionProcessor extends ChainProcessor
{
    /** @var string */
    protected $action;

    /**
     * @param ProcessorBag $processorBag
     * @param string       $action
     */
    public function __construct(ProcessorBag $processorBag, $action)
    {
        parent::__construct($processorBag);
        $this->action = $action;
    }

    /**
     * Gets an action that is handled by the processor.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Creates an instance of Context this processor works with.
     *
     * @return Context
     */
    final public function createContext()
    {
        $context = $this->createContextObject();
        $this->initializeContextObject($context);

        return $context;
    }

    /**
     * Creates new Context object.
     *
     * @return Context
     */
    protected function createContextObject()
    {
        return new Context();
    }

    /**
     * Initializes new Context object.
     *
     * @param Context $context
     */
    protected function initializeContextObject(Context $context)
    {
        $context->setAction($this->action);
    }
}
