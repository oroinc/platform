<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

/**
 * Defines the contract for guessing inline editing configuration options for datagrid columns.
 *
 * Implementations of this interface analyze column metadata and entity information to automatically
 * determine appropriate inline editing settings, such as editor types, validation rules,
 * and frontend configuration. This allows datagrids to support inline editing with minimal manual configuration.
 */
interface GuesserInterface
{
    /**
     * @param string $columnName
     * @param string $entityName
     * @param array $column
     * @param bool $isEnabledInline
     *
     * @return array
     */
    public function guessColumnOptions($columnName, $entityName, $column, $isEnabledInline = false);
}
