<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

/**
 * Class MultiSelectGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class MultiSelectGuesser implements GuesserInterface
{
    const MULTI_SELECT = 'multi-select';

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $result = [];
        if (array_key_exists(Configuration::FRONTEND_TYPE_NAME, $column)
            && $column[Configuration::FRONTEND_TYPE_NAME] === self::MULTI_SELECT) {
        }

        return $result;
    }
}
