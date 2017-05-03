<?php

namespace Oro\Bundle\WorkflowBundle\Processor;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Context\ValidateTransitionContextTrait;
use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TransitActionProcessor extends ActionProcessor
{
    use ValidateTransitionContextTrait;

    const ACTION = 'transit';

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ProcessorBagInterface $processorBag
     * @param LoggerInterface|null $logger
     */
    public function __construct(ProcessorBagInterface $processorBag, LoggerInterface $logger = null)
    {
        parent::__construct($processorBag, self::ACTION);

        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    protected function executeProcessors(ContextInterface $context)
    {
        $this->validateContextType($context);

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

    /**
     * @return ContextInterface|TransitionContext
     */
    protected function createContextObject(): ContextInterface
    {
        return new TransitionContext();
    }
}
