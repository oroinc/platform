<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

interface ObjectAccessorInterface extends ObjectPropertyAccessorInterface
{
    /**
     * Returns FQCN of a given object.
     *
     * @param mixed $object
     *
     * @return string|null
     */
    public function getClassName($object);

    /**
     * Returns an array contains all properties of a given object.
     *
     * @param mixed $object
     *
     * @return array
     */
    public function toArray($object);
}
