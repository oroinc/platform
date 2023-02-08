<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * A base processor for completing paths for errors of batch items.
 */
abstract class CompleteItemErrorPaths implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $items = $context->getBatchItems();
        if (!$items) {
            return;
        }

        $file = $context->getFile();
        $firstRecordOffset = $file->getFirstRecordOffset();
        $sectionName = $file->getSectionName();
        foreach ($items as $item) {
            $errors = $item->getContext()->getErrors();
            if (empty($errors)) {
                continue;
            }

            $itemOffset = $firstRecordOffset + $item->getIndex();
            foreach ($errors as $error) {
                $this->completeItemErrorPath($error, $item, $itemOffset, $sectionName);
            }
        }
    }

    abstract protected function completeItemErrorPath(
        Error $error,
        BatchUpdateItem $item,
        int $itemOffset,
        ?string $sectionName
    ): void;
}
