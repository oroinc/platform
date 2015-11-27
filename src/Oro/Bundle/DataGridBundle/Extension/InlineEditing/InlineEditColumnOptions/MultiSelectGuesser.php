<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;

/**
 * Class MultiSelectGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class MultiSelectGuesser extends ChoicesGuesser
{
    /** Frontend type */
    const MULTI_SELECT = 'multi-select';

    const DEFAULT_EDITOR_VIEW = 'orodatagrid/js/app/views/editor/multi-select-editor-view';

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $metadata = $entityManager->getClassMetadata($entityName);
        $result = [];

        $isConfigured = isset($column[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY]);
        $isConfigured = $isConfigured
            || isset($column[Configuration::BASE_CONFIG_KEY][Configuration::AUTOCOMPLETE_API_ACCESSOR_KEY]);
        if (!$isConfigured && $metadata->hasAssociation($columnName)) {
            $mapping = $metadata->getAssociationMapping($columnName);
            if ($mapping['type'] === ClassMetadata::MANY_TO_MANY) {
                $targetEntity = $metadata->getAssociationTargetClass($columnName);

                $targetEntityMetadata = $entityManager->getClassMetadata($targetEntity);
                if (
                    isset($column[Configuration::BASE_CONFIG_KEY]
                        [Configuration::VIEW_OPTIONS_KEY][Configuration::VALUE_FIELD_NAME_KEY])
                ) {
                    $labelField =
                        $column[Configuration::BASE_CONFIG_KEY]
                            [Configuration::VIEW_OPTIONS_KEY][Configuration::VALUE_FIELD_NAME_KEY];
                } else {
                    $labelField = $this->guessLabelField($targetEntityMetadata, $columnName);
                }

                $result[Configuration::BASE_CONFIG_KEY] = [Configuration::CONFIG_ENABLE_KEY => true];
                $result[PropertyInterface::FRONTEND_TYPE_KEY] = self::MULTI_SELECT;
                $result[PropertyInterface::TYPE_KEY] = 'field';

                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();
                $result[Configuration::CHOICES_KEY] = $this->getChoices($targetEntity, $keyField, $labelField);

                $isConfiguredInlineEdit = array_key_exists(Configuration::BASE_CONFIG_KEY, $column);
                $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);
            }
        }

        return $result;
    }
}
