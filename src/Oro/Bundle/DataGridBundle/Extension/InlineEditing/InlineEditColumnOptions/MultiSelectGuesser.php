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

    const DEFAULT_EDITOR_VIEW = 'oroform/js/app/views/editor/multi-checkbox-editor-view';

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column, $isEnabledInline = false)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $metadata = $entityManager->getClassMetadata($entityName);

        $result = [];
        if (!$this->isConfiguredAccessor($column) && $metadata->hasAssociation($columnName)) {
            $mapping = $metadata->getAssociationMapping($columnName);
            if ($mapping['type'] === ClassMetadata::MANY_TO_MANY) {
                if ($isEnabledInline) {
                    $result[Configuration::BASE_CONFIG_KEY] = [Configuration::CONFIG_ENABLE_KEY => true];
                }
                $result[PropertyInterface::FRONTEND_TYPE_KEY] = self::MULTI_SELECT;
                $result[PropertyInterface::TYPE_KEY] = 'field';

                $targetEntity = $metadata->getAssociationTargetClass($columnName);
                $targetEntityMetadata = $entityManager->getClassMetadata($targetEntity);
                $labelField = $this->getLabelField($columnName, $column, $targetEntityMetadata);
                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();
                $result[Configuration::CHOICES_KEY] = $this->getChoices($targetEntity, $keyField, $labelField);

                $isConfiguredInlineEdit = array_key_exists(Configuration::BASE_CONFIG_KEY, $column);
                $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);
            }
        }

        return $result;
    }
}
