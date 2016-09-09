<?php

namespace Oro\Bundle\ApiBundle\Debug;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\Debug\TraceableActionProcessor;
use Oro\Component\ChainProcessor\Debug\TraceLogger;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;

class TraceableActionProcessorBag implements ActionProcessorBagInterface
{
    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var TraceLogger */
    protected $logger;

    /**
     * @param ActionProcessorBagInterface $processorBag
     * @param TraceLogger                 $logger
     */
    public function __construct(ActionProcessorBagInterface $processorBag, TraceLogger $logger)
    {
        $this->processorBag = $processorBag;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function addProcessor(ActionProcessorInterface $processor)
    {
        $this->processorBag->addProcessor(
            new TraceableActionProcessor($processor, $this->logger)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessor($action)
    {
        return $this->processorBag->getProcessor($action);
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        return $this->processorBag->getActions();
    }
}
