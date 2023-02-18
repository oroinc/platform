<?php

namespace Oro\Bundle\WorkflowBundle\Processor;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * The main processor for "transit" action.
 */
class TransitActionProcessor extends ActionProcessor
{
    private LoggerInterface $logger;

    public function __construct(ProcessorBagInterface $processorBag, LoggerInterface $logger)
    {
        parent::__construct($processorBag, 'transit');
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): TransitionContext
    {
        return new TransitionContext();
    }

    /**
     * {@inheritDoc}
     */
    protected function executeProcessors(ContextInterface $context): void
    {
        /** TransitionContext $context */

        $processors = $this->processorBag->getProcessors($context);

        /** @var ProcessorInterface $processor */
        foreach ($processors as $processor) {
            try {
                $this->logger->debug(
                    'Execute processor {processorId}',
                    [
                        'processorId' => $processors->getProcessorId(),
                        'processorAttributes' => $processors->getProcessorAttributes()
                    ]
                );

                $processor->process($context);

                $this->logger->debug('Context processed.', ['context' => $context->toArray()]);
            } catch (\Exception $e) {
                throw new ExecutionFailedException(
                    $processors->getProcessorId(),
                    $processors->getAction(),
                    $processors->getGroup(),
                    $e
                );
            }
        }
    }
}
