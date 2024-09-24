<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * The default implementation of a service to get human-readable names in English of entity classes.
 */
class EntityClassNameProvider extends AbstractEntityClassNameProvider implements EntityClassNameProviderInterface
{
    #[\Override]
    public function getEntityClassName(string $entityClass): ?string
    {
        return $this->getName($entityClass);
    }

    #[\Override]
    public function getEntityClassPluralName(string $entityClass): ?string
    {
        return $this->getName($entityClass, true);
    }
}
