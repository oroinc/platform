<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * Provides an interface to the service that can get the entity identifier.
 */
interface ObjectIdentityAccessorInterface
{
    /**
     * Returns a unique identifier of the given object
     *
     * @param object $entity The entity object
     *
     * @return mixed
     */
    public function getIdentifier($entity);
}
