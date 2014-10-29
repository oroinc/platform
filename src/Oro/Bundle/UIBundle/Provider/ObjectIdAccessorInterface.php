<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * Provides an interface to the service that can be used to get an unique identifier of an object.
 */
interface ObjectIdAccessorInterface
{
    /**
     * Returns an unique identifier of the given object
     *
     * @param object $object The entity object
     *
     * @return mixed
     */
    public function getIdentifier($object);
}
