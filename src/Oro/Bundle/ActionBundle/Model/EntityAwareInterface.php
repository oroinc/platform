<?php

namespace Oro\Bundle\ActionBundle\Model;

/**
 * Defines the contract for objects that are aware of and can provide an entity.
 */
interface EntityAwareInterface
{
    /**
     * @return object
     */
    public function getEntity();
}
