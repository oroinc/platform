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
     * @param array  $column
     *
     * @return array
     *
     * @throws \Exception
     */
    public function guessColumnOptions($columnName, $entityName, $column);
}
