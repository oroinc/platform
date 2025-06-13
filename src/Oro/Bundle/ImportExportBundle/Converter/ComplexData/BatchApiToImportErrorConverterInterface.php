<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

use Oro\Bundle\ApiBundle\Batch\Model\BatchError;

/**
 * Represents a service that converts Batch API errors to errors in import format.
 */
interface BatchApiToImportErrorConverterInterface
{
    public function convertToImportError(BatchError $error, array $requestData, ?int $rowIndex = null): string;
}
