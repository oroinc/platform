<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes the entity mapper from the context for all batch items.
 */
class RemoveEntityMapper implements ProcessorInterface
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

        foreach ($items as $item) {
            $itemTargetContext = $item->getContext()->getTargetContext();
            if ($itemTargetContext instanceof FormContext) {
                $itemTargetContext->setEntityMapper(null);
            }
        }
    }
}
