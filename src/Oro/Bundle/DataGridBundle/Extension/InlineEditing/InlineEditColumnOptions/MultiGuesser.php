<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

/**
 * Class MultiGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class MultiGuesser implements GuesserInterface
{
    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $result = [];
        if (array_key_exists('frontend_type', $column) && $column['frontend_type'] === 'multi-select') {
        }

        return $result;
    }
}
