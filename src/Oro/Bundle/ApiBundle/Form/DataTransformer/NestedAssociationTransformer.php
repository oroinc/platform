<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;

/**
 * Transforms class name and identifier of an entity to an instance of EntityIdentifier object.
 */
class NestedAssociationTransformer extends AbstractEntityAssociationTransformer
{
    /**
     * {@inheritdoc}
     */
    protected function getEntity($entityClass, $entityId)
    {
        $entity = $this->loadEntity($entityClass, $entityId);
        $entityClass = ClassUtils::getClass($entity);
        $entityId = $this->doctrineHelper
            ->getEntityMetadataForClass($entityClass)
            ->getIdentifierValues($entity);
        if (\count($entityId) === 1) {
            $entityId = reset($entityId);
        }

        return new EntityIdentifier($entityId, $entityClass);
    }
}
