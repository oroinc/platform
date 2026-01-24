<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

/**
 * Defines the contract for converting values to DateTime objects.
 *
 * Implementations handle the conversion of various string representations
 * to DateTime objects, supporting different date/time types and formats
 * used in import/export operations.
 */
interface DateTimeTypeConverterInterface
{
    /**
     * Convert value to \DateTime object.
     *
     * @param string $value
     * @param string $type
     *
     * @return mixed
     */
    public function convertToDateTime($value, $type);
}
