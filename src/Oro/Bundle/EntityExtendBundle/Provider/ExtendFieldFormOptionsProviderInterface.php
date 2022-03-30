<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

/**
 * Interface for classes that provide form options for the specified extend field.
 */
interface ExtendFieldFormOptionsProviderInterface
{
    public function getOptions(string $className, string $fieldName): array;
}
