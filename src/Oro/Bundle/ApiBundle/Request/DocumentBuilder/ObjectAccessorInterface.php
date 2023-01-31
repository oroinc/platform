<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

/**
 * An interface for classes that can provide an access to properties and metadata of a different object types.
 */
interface ObjectAccessorInterface extends ObjectPropertyAccessorInterface
{
    /**
     * Returns FQCN of a given object.
     */
    public function getClassName(mixed $object): ?string;

    /**
     * Returns an array contains all properties of a given object.
     */
    public function toArray(mixed $object): array;
}
