<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Executes the target action processors from the groups are set to the target action context.
 */
class ExecuteTargetProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        $targetProcessor = $context->getTargetProcessor();
        if (null === $targetProcessor) {
            throw new RuntimeException('The target processor is not defined.');
        }
        $targetContext = $context->getTargetContext();
        if (null === $targetContext) {
            throw new RuntimeException('The target context is not defined.');
        }

        $this->assertTargetContext($targetContext);
        $targetProcessor->process($targetContext);
        $this->syncContext($targetContext, $context);
        if ($targetContext->hasErrors()) {
            $this->syncErrors($targetContext, $context);
        }
    }

    protected function assertTargetContext(ContextInterface $targetContext): void
    {
        if (!$targetContext->getFirstGroup()) {
            throw new RuntimeException('The target first group is not defined.');
        }
        if (!$targetContext->getLastGroup()) {
            throw new RuntimeException('The target last group is not defined.');
        }
    }

    protected function syncContext(ContextInterface $targetContext, BatchUpdateItemContext $context): void
    {
    }

    protected function syncErrors(Context $targetContext, BatchUpdateItemContext $context): void
    {
        $errors = $targetContext->getErrors();
        foreach ($errors as $error) {
            $context->addError($error);
        }
        $targetContext->resetErrors();
    }
}
