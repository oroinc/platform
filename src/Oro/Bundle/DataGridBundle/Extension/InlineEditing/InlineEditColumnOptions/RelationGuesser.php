<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;

/**
 * Class RelationGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class RelationGuesser implements GuesserInterface
{
    const DEFAULT_EDITOR_VIEW = 'orodatagrid/js/app/views/editor/related-id-relation-editor-view';
    const DEFAULT_API_ACCESSOR_CLASS = 'oroui/js/tools/search-api-accessor';

    const RELATION = 'relation';

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $result = [];
        if (array_key_exists(Configuration::FRONTEND_TYPE_KEY, $column)
            && $column[Configuration::FRONTEND_TYPE_KEY] === self::RELATION) {
            $isConfiguredInlineEdit = array_key_exists(Configuration::BASE_CONFIG_KEY, $column);
            $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);
            $result = $this->guessApiAccessorClass($column, $isConfiguredInlineEdit, $result);
        }

        return $result;
    }

    /**
     * @param $column
     * @param $isConfiguredInlineEdit
     * @param $result
     *
     * @return array
     */
    protected function guessEditorView($column, $isConfiguredInlineEdit, $result)
    {
        $isConfigured = $isConfiguredInlineEdit
            && array_key_exists(Configuration::EDITOR_KEY, $column[Configuration::BASE_CONFIG_KEY]);
        $isConfigured = $isConfigured
            && array_key_exists(
                Configuration::VIEW_KEY,
                $column[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY]
            );
        if (!$isConfigured) {
            $result[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY][Configuration::VIEW_KEY]
                = RelationGuesser::DEFAULT_EDITOR_VIEW;
        }

        return $result;
    }

    /**
     * @param $column
     * @param $isConfiguredInlineEdit
     * @param $result
     *
     * @return array
     */
    protected function guessApiAccessorClass($column, $isConfiguredInlineEdit, $result)
    {
        $isConfigured = $isConfiguredInlineEdit
            && array_key_exists(Configuration::AUTOCOMPLETE_API_ACCESSOR_KEY, $column[Configuration::BASE_CONFIG_KEY]);
        $isConfigured = $isConfigured
            && array_key_exists(
                Configuration::CLASS_KEY,
                $column[Configuration::BASE_CONFIG_KEY][Configuration::AUTOCOMPLETE_API_ACCESSOR_KEY]
            );
        if (!$isConfigured) {
            $result[Configuration::BASE_CONFIG_KEY]
                [Configuration::AUTOCOMPLETE_API_ACCESSOR_KEY]
                [Configuration::CLASS_KEY]
                    = RelationGuesser::DEFAULT_API_ACCESSOR_CLASS;
        }

        return $result;
    }
}
