<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts an entity to a model and adds the model to the context instead of the entity.
 */
class ConvertEntityToModel implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $entity = $context->getResult();
        if (!\is_object($entity)) {
            // an entity does not exist
            return;
        }

        $entityMapper = $context->getEntityMapper();
        if (null === $entityMapper) {
            // the entity mapper was not initialized
            return;
        }

        $context->setResult(
            $entityMapper->getModel($entity, $context->getClassName())
        );
    }
}
