<?php

namespace Oro\Bundle\DashboardBundle\Model;

/**
 * Defines the contract for models that wrap entity objects.
 *
 * This interface is implemented by model classes that provide a layer of abstraction
 * over entity objects, allowing additional behavior, computed properties, or presentation
 * logic to be added without modifying the entity classes themselves. It ensures that
 * models can provide access to their underlying entity when needed.
 */
interface EntityModelInterface
{
    /**
     * @return object
     */
    public function getEntity();
}
