<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts a model to an entity and adds the entity to the context instead of the model.
 */
class ConvertModelToEntity implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

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
