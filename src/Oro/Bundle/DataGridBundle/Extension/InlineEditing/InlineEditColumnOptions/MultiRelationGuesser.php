<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

/**
 * Class MultiRelationGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class MultiRelationGuesser implements GuesserInterface
{
    const MULTI_RELATION = 'multi-relation';

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $result = [];
        if (array_key_exists(Configuration::FRONTEND_TYPE_NAME, $column)
            && $column[Configuration::FRONTEND_TYPE_NAME] === self::MULTI_RELATION) {
        }

        return $result;
    }
}
