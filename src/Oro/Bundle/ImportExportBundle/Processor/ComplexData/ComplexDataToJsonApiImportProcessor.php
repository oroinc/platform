<?php

namespace Oro\Bundle\ImportExportBundle\Processor\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\JsonApiImportConverter;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;

/**
 * Converts input array data to the JSON:API format.
 */
class ComplexDataToJsonApiImportProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly JsonApiImportConverter $dataConverter
    ) {
    }

    #[\Override]
    public function process($item)
    {
        return $this->dataConverter->convert($item);
    }
}
