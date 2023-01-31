<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Creates the target action context and adds it to the context.
 */
class SetTargetContext implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateItemContext $context */

        $targetContext = $context->getTargetContext();
        if (null !== $targetContext) {
            // the target context was already set
            return;
        }

        $targetProcessor = $context->getTargetProcessor();
        if (null === $targetProcessor) {
            throw new RuntimeException('The target processor is not defined.');
        }

        $targetContext = $targetProcessor->createContext();
        $this->initializeTargetContext($targetContext, $context);
        $context->setTargetContext($targetContext);
    }

    private function initializeTargetContext(ContextInterface $targetContext, BatchUpdateItemContext $context): void
    {
        if ($targetContext instanceof Context) {
            $targetContext->setVersion($context->getVersion());
            $targetContext->getRequestType()->set($context->getRequestType());
            $targetContext->getRequestType()->add(RequestType::BATCH);
            $targetContext->setSharedData($context->getSharedData());

            $entityClass = $context->getClassName();
            if (null !== $entityClass) {
                $targetContext->setClassName($entityClass);
            }
        }
        $entityId = $context->getId();
        if (null !== $entityId && $targetContext instanceof SingleItemContext) {
            $targetContext->setId($entityId);
        }
        if ($targetContext instanceof FormContext) {
            $targetContext->setRequestData($context->getRequestData());
        }
        if ($targetContext instanceof NormalizeResultContext) {
            $targetContext->setSoftErrorsHandling(true);
        }
    }
}
