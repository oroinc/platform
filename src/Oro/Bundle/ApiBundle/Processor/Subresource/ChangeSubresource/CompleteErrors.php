<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\CompleteErrorsTrait;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if there are any errors in the context,
 * and if so, completes missing properties of all Error objects.
 * E.g. if an error is created due to an exception occurs,
 * such error does not have "statusCode", "title", "detail" and other properties,
 * and these properties are extracted from the Exception object.
 * Also, removes duplicated errors if any.
 */
class CompleteErrors implements ProcessorInterface
{
    use CompleteErrorsTrait;

    private ErrorCompleterRegistry $errorCompleterRegistry;

    public function __construct(ErrorCompleterRegistry $errorCompleterRegistry)
    {
        $this->errorCompleterRegistry = $errorCompleterRegistry;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

        if (!$context->hasErrors()) {
            // no errors
            return;
        }

        $requestType = $context->getRequestType();
        $this->completeErrors(
            $context->getErrors(),
            $this->errorCompleterRegistry->getErrorCompleter($requestType),
            $requestType,
            $this->getRequestMetadata($context)
        );
        $this->removeDuplicates($context);
    }

    private function getRequestMetadata(ChangeSubresourceContext $context): ?EntityMetadata
    {
        if (!$this->isEntityClass($context->getParentClassName())) {
            return null;
        }
        if (!$this->isEntityClass($context->getRequestClassName())) {
            return null;
        }

        try {
            return $context->getRequestMetadata();
        } catch (\Exception $e) {
            return null;
        }
    }
}
