<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * An interface for classes that can provide substitututions for entity class names.
 */
interface EntityOverrideProviderInterface
{
    /**
     * Returns the class name that should be used instead the given entity class.
     *
     * @param string $entityClass
     *
     * @return string|null The class name that substitute the given class name
     *                     or NULL if there is no substitutution
     */
    public function getSubstituteEntityClass(string $entityClass): ?string;
}
