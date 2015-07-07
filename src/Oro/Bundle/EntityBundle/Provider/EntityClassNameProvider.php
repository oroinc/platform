<?php

namespace Oro\Bundle\EntityBundle\Provider;

class EntityClassNameProvider extends AbstractEntityClassNameProvider implements EntityClassNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClassName($entityClass)
    {
        return $this->getName($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassPluralName($entityClass)
    {
        return $this->getName($entityClass, true);
    }
}
