<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the status code for the HTTP response of the synchronous Batch API operation.
 */
class SetSynchronousOperationHttpResponseStatusCode implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if (null !== $context->getResponseStatusCode()) {
            // the status code is already set
            return;
        }

        if ($context->hasResult() && !$context->hasErrors() && !$this->isSingleItemResponse($context)) {
            $context->setResponseStatusCode(Response::HTTP_OK);
        }
    }

    private function isSingleItemResponse(UpdateListContext $context): bool
    {
        return
            !$context->isSynchronousMode()
            || is_a($context->getMetadata()->getClassName(), AsyncOperation::class, true);
    }
}
