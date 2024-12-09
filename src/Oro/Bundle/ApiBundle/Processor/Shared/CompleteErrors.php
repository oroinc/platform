<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
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
        /** @var Context $context */

        if (!$context->hasErrors()) {
            // no errors
            return;
        }

        $requestType = $context->getRequestType();
        $this->completeErrors(
            $context->getErrors(),
            $this->errorCompleterRegistry->getErrorCompleter($requestType),
            $requestType,
            $this->getMetadata($context)
        );
        $this->removeDuplicates($context);
    }

    private function getMetadata(Context $context): ?EntityMetadata
    {
        if (!$this->isEntityClass($context->getClassName())) {
            return null;
        }

        try {
            return $context->getMetadata();
        } catch (\Exception $e) {
            return null;
        }
    }
}
