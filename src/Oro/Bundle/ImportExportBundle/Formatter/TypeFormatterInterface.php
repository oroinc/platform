<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

/**
 * Defines the contract for formatting values by type during export.
 *
 * Implementations handle the conversion of various data types to their formatted
 * string representations suitable for export, such as formatting dates, numbers,
 * and other types according to locale and type-specific rules.
 */
interface TypeFormatterInterface
{
    /**
     * Formats value by provided type.
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    public function formatType($value, $type);
}
