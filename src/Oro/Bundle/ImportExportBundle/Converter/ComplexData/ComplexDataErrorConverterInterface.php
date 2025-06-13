<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

/**
 * Represents a converter that allows to modify an import error.
 */
interface ComplexDataErrorConverterInterface
{
    public function convertError(string $error, ?string $propertyPath): string;
}
