<?php

namespace Oro\Bundle\EntityBundle\Provider;

interface EntityClassNameProviderInterface
{
    /**
     * Returns the human-readable name in English of the given entity class.
     *
     * @param string $entityClass
     *
     * @return string|null
     */
    public function getEntityClassName($entityClass);

    /**
     * Returns the human-readable plural name in English of the given entity class.
     *
     * @param string $entityClass
     *
     * @return string|null
     */
    public function getEntityClassPluralName($entityClass);
}
