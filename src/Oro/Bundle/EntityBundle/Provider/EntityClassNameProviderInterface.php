<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Represents a service to get human-readable names in English of entity classes.
 */
interface EntityClassNameProviderInterface
{
    /**
     * Returns the human-readable name in English of the given entity class.
     */
    public function getEntityClassName(string $entityClass): ?string;

    /**
     * Returns the human-readable plural name in English of the given entity class.
     */
    public function getEntityClassPluralName(string $entityClass): ?string;
}
