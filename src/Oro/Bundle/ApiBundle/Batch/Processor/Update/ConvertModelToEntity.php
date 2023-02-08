<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts a model to an ORM entity and adds the entity to the context instead of the model
 * for all batch items that do not have errors.
 */
class ConvertModelToEntity implements ProcessorInterface
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
            $itemContext = $item->getContext();
            if (!$itemContext->hasErrors()) {
                $itemTargetContext = $itemContext->getTargetContext();
                if ($itemTargetContext instanceof FormContext) {
                    $this->convertModelToEntity($itemTargetContext);
                }
            }
        }
    }

    private function convertModelToEntity(FormContext $context): void
    {
        $model = $context->getResult();
        if (!\is_object($model)) {
            // a model does not exist
            return;
        }

        $entityMapper = $context->getEntityMapper();
        if (null === $entityMapper) {
            // the entity mapper was not initialized
            return;
        }

        $entityClass = $context->getClassName();
        $config = $context->getConfig();
        if (null !== $config) {
            $parentResourceClass = $config->getParentResourceClass();
            if ($parentResourceClass) {
                $entityClass = $parentResourceClass;
            }
        }

        $context->setResult(
            $entityMapper->getEntity($model, $entityClass)
        );
    }
}
