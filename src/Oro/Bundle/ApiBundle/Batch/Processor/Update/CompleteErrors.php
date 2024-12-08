<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\CompleteErrorsTrait;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if there are any errors in the context or contexts of batch items,
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

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $requestType = $context->getRequestType();
        $errorCompleter = $this->errorCompleterRegistry->getErrorCompleter($requestType);
        $this->completeErrors($context->getErrors(), $errorCompleter, $requestType, null);
        $items = $context->getBatchItems();
        if ($items) {
            foreach ($items as $item) {
                $itemContext = $item->getContext();
                if ($itemContext->hasErrors()) {
                    $this->completeErrors(
                        $itemContext->getErrors(),
                        $errorCompleter,
                        $requestType,
                        $this->getItemMetadata($item)
                    );
                    $this->removeDuplicates($itemContext);
                }
            }
        }
    }

    private function getItemMetadata(BatchUpdateItem $item): ?EntityMetadata
    {
        $targetContext = $item->getContext()->getTargetContext();
        if (null === $targetContext) {
            return null;
        }
        if (!$this->isEntityClass($targetContext->getClassName())) {
            return null;
        }

        try {
            return $targetContext->getMetadata();
        } catch (\Exception $e) {
            return null;
        }
    }
}
