<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the processor responsible to handle the target action to the context.
 */
class SetTargetProcessor implements ProcessorInterface
{
    private ActionProcessorBagInterface $processorBag;

    public function __construct(ActionProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        $targetProcessor = $context->getTargetProcessor();
        if (null !== $targetProcessor) {
            // the target processor was already set
            return;
        }
        $targetAction = $context->getTargetAction();
        if (!$targetAction) {
            throw new RuntimeException('The target action is not defined.');
        }

        $context->setTargetProcessor($this->processorBag->getProcessor($targetAction));
    }
}
