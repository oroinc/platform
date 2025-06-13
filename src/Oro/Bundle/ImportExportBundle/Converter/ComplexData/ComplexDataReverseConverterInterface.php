<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

/**
 * Represents a converter that allows to modify entity data to be exported.
 */
interface ComplexDataReverseConverterInterface
{
    public function reverseConvert(array $item, object $sourceEntity): array;
}
