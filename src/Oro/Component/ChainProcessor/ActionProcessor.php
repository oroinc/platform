<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base class for action processors.
 */
class ActionProcessor extends ChainProcessor implements ActionProcessorInterface
{
    private string $action;

    public function __construct(ProcessorBagInterface $processorBag, string $action)
    {
        parent::__construct($processorBag);
        $this->action = $action;
    }

    /**
     * {@inheritDoc}
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * {@inheritDoc}
     */
    final public function createContext(): Context
    {
        $context = $this->createContextObject();
        $this->initializeContextObject($context);

        return $context;
    }

    /**
     * Creates new Context object.
     */
    protected function createContextObject(): Context
    {
        return new Context();
    }

    /**
     * Initializes new Context object.
     */
    protected function initializeContextObject(Context $context): void
    {
        $context->setAction($this->action);
    }
}
