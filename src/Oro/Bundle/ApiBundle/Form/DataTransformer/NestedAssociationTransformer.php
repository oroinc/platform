<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;

class NestedAssociationTransformer extends AbstractEntityAssociationTransformer
{
    /**
     * {@inheritdoc}
     */
    protected function getEntity($entityClass, $entityId)
    {
        $entity = $this->loadEntity($entityClass, $entityId);
        $entityClass = ClassUtils::getClass($entity);
        $entityId = $this->doctrine
            ->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass)
            ->getIdentifierValues($entity);
        if (count($entityId) === 1) {
            $entityId = reset($entityId);
        }

        return new EntityIdentifier($entityId, $entityClass);
    }
}
