<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;

class TraceableActionProcessor implements ActionProcessorInterface
{
    /** @var ActionProcessorInterface */
    protected $actionProcessor;

    /** @var TraceLogger */
    protected $logger;

    /**
     * @param ActionProcessorInterface $actionProcessor
     * @param TraceLogger              $logger
     */
    public function __construct(ActionProcessorInterface $actionProcessor, TraceLogger $logger)
    {
        $this->actionProcessor = $actionProcessor;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction()
    {
        return $this->actionProcessor->getAction();
    }

    /**
     * {@inheritdoc}
     */
    public function createContext()
    {
        return $this->actionProcessor->createContext();
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
