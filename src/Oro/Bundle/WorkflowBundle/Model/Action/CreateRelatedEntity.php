<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Class CreateRelatedEntity.
 *
 * Create workflow entity and set it to corresponding property of context
 */
class CreateRelatedEntity extends CreateEntity
{
    /**
     * {@inheritdoc}
     */
    protected function createObject($context)
    {
        $entity = parent::createObject($context);
        if ($context instanceof WorkflowItem) {
            $context->setEntity($entity);
        }

        return $entity;
    }
}
