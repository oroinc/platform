<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the synchronous Batch API operation result document.
 */
class SetSynchronousOperationResultDocument implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if (null !== $context->getResponseDocumentBuilder()) {
            // the result document will be set via the response document builder
            return;
        }

        $result = $context->getResult();
        if (!\is_array($result) || !\array_key_exists(ProcessSynchronousOperation::PRIMARY_DATA, $result)) {
            // the result document is already set
            return;
        }

        if (\array_key_exists(ProcessSynchronousOperation::INCLUDED_DATA, $result)) {
            throw new \LogicException(sprintf(
                'The synchronous Batch API operation result document cannot be set for the "%s" request type.',
                (string)$context->getRequestType()
            ));
        }

        $context->setResult($result[ProcessSynchronousOperation::PRIMARY_DATA]);
    }
}
