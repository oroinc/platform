<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\Context;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Decorates an action processor to log information about processor execution.
 */
class TraceableActionProcessor implements ActionProcessorInterface
{
    private ActionProcessorInterface $actionProcessor;
    private TraceLogger $logger;

    public function __construct(ActionProcessorInterface $actionProcessor, TraceLogger $logger)
    {
        $this->actionProcessor = $actionProcessor;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function getAction(): string
    {
        return $this->actionProcessor->getAction();
    }

    /**
     * {@inheritDoc}
     */
    public function createContext(): Context
    {
        return $this->actionProcessor->createContext();
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        $this->logger->startAction($this->getAction());
        try {
            $this->actionProcessor->process($context);
        } catch (\Exception $e) {
            $this->logger->stopAction($e);
            throw $e;
        }
        $this->logger->stopAction();
    }
}
