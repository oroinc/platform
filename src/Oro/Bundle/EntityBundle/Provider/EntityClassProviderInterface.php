<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Defines the contract for providing entity class names.
 *
 * Implementations of this interface return a list of all entity class names
 * that are available in the application.
 */
interface EntityClassProviderInterface
{
    /**
     * Returns a list of entity class names.
     *
     * @return string[]
     */
    public function getClassNames();
}
