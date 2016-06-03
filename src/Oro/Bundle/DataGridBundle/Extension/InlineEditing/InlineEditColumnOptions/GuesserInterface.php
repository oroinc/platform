<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

/**
 * Interface GuesserInterface
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
interface GuesserInterface
{
    /**
     * @param string $columnName
     * @param string $entityName
     * @param array $column
     * @param bool $enableInlineEditing
     *
     * @return array
     */
    public function guessColumnOptions($columnName, $entityName, $column, $enableInlineEditing = false);
}
