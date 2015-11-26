<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;

/**
 * Class MultiGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class MultiGuesser extends ChoicesGuesser
{
    const DEFAULT_EDITOR_VIEW = 'orodatagrid/js/app/views/editor/multi-select-editor-view';

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $metadata = $this->entityManager->getClassMetadata($entityName);
        $result = [];

        if ($columnName === 'MultiSelect') {
            $column['frontend_type'] = 'multi-select';
        }

        if (array_key_exists('frontend_type', $column)
            && $column['frontend_type'] === 'multi-select'
            && $metadata->hasAssociation($columnName)
        ) {
            $isConfiguredInlineEdit = array_key_exists(Configuration::BASE_CONFIG_KEY, $column);
            $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);

            $mapping = $metadata->getAssociationMapping($columnName);
            if ($mapping['type'] === ClassMetadata::MANY_TO_MANY) {
                $targetEntity = $metadata->getAssociationTargetClass($columnName);

                $targetEntityMetadata = $this->entityManager->getClassMetadata($targetEntity);
                if (isset($column[Configuration::BASE_CONFIG_KEY]['view_options']['value_field_name'])) {
                    $labelField = $column[Configuration::BASE_CONFIG_KEY]['view_options']['value_field_name'];
                } else {
                    $labelField = $this->guessLabelField($targetEntityMetadata, $columnName);
                }

                $result[Configuration::BASE_CONFIG_KEY] = ['enable' => true];

                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();
                $result['choices'] = $this->getChoices($targetEntity, $keyField, $labelField);
            }
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
        $editorKey = 'editor';
        $viewKey = 'view';
        $isConfigured = $isConfiguredInlineEdit
            && array_key_exists($editorKey, $column[Configuration::BASE_CONFIG_KEY]);
        $isConfigured = $isConfigured
            && array_key_exists($viewKey, $column[Configuration::BASE_CONFIG_KEY][$editorKey]);
        if (!$isConfigured) {
            $result[Configuration::BASE_CONFIG_KEY][$editorKey][$viewKey] = RelationGuesser::DEFAULT_EDITOR_VIEW;
        }

        return $result;
    }
}
