<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

/**
 * Represents a converter that allows to modify entity data to be imported.
 */
interface ComplexDataConverterInterface
{
    public const string TARGET_TYPE = 'target_type';
    public const string ENTITY = 'entity';
    public const string INCLUDED = 'included';
    public const string ERRORS = 'errors';
    public const string ERROR_MESSAGE = 'message';
    public const string ERROR_PATH = 'path';

    public function convert(array $item, mixed $sourceData): array;
}
