<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base class for action processors.
 */
class ActionProcessor extends ChainProcessor implements ActionProcessorInterface
{
    /** @var string */
    protected $action;

    /**
     * @param ProcessorBagInterface $processorBag
     * @param string                $action
     */
    public function __construct(ProcessorBagInterface $processorBag, $action)
    {
        parent::__construct($processorBag);
        $this->action = $action;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * {@inheritdoc}
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
