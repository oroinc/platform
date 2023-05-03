<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts the parent model to an ORM entity and adds the entity to the context instead of the model.
 */
class ConvertParentModelToEntity implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        $parentModel = $context->getParentEntity();
        if (!\is_object($parentModel)) {
            // a model does not exist
            return;
        }

        $entityMapper = $context->getEntityMapper();
        if (null === $entityMapper) {
            // the entity mapper was not initialized
            return;
        }

        $parentEntityClass = $context->getParentClassName();
        $parentConfig = $context->getParentConfig();
        if (null !== $parentConfig) {
            $parentResourceClass = $parentConfig->getParentResourceClass();
            if ($parentResourceClass) {
                $parentEntityClass = $parentResourceClass;
            }
        }

        $context->setParentEntity(
            $entityMapper->getEntity($parentModel, $parentEntityClass)
        );
    }
}
