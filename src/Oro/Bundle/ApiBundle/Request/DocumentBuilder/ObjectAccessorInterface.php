<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

/**
 * An interface for classes that can provide an access to properties and metadata of a different object types.
 */
interface ObjectAccessorInterface extends ObjectPropertyAccessorInterface
{
    /**
     * Returns FQCN of a given object.
     *
     * @param mixed $object
     *
     * @return string|null
     */
    public function getClassName($object): ?string;

    /**
     * Returns an array contains all properties of a given object.
     *
     * @param mixed $object
     *
     * @return array
     */
    public function toArray($object): array;
}
