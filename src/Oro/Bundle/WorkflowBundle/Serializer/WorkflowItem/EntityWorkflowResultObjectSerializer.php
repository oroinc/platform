<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Converts an entity stored in WorkflowResult to an array.
 * An entity is represented in the serialization result by its identifier.
 */
class EntityWorkflowResultObjectSerializer implements WorkflowResultObjectSerializerInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(object $object): ?array
    {
        if (!$this->doctrineHelper->isManageableEntity($object)) {
            return null;
        }

        return $this->doctrineHelper->getEntityIdentifier($object);
    }
}
