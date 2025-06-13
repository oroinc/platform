<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\ValueTransformer;

/**
 * Represents a service that is used to transform a value to appropriate data-type.
 */
interface ComplexDataValueTransformerInterface
{
    public function transformValue(mixed $value, ?string $dataType): mixed;
}
