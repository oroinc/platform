<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration as Config;

/**
 * Guesses options for "multi-select" columns.
 */
class MultiSelectGuesser extends ChoicesGuesser
{
    /**
     * {@inheritDoc}
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
                    $result[Config::BASE_CONFIG_KEY] = [Config::CONFIG_ENABLE_KEY => true];
                }
                $result[PropertyInterface::FRONTEND_TYPE_KEY] = 'multi-select';
                $result[PropertyInterface::TYPE_KEY] = 'field';

                $targetEntity = $metadata->getAssociationTargetClass($columnName);
                $targetEntityMetadata = $entityManager->getClassMetadata($targetEntity);
                $labelField = $this->getLabelField($columnName, $column, $targetEntityMetadata);
                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();

                $translatable = isset($column['translatable']) && $column['translatable'] === true;
                $result[Config::CHOICES_KEY] = $this->choiceHelper
                    ->getChoices($targetEntity, $keyField, $labelField, null, $translatable);
                if ($translatable) {
                    $result['translatable_options'] = false;
                }

                $isConfiguredInlineEdit = \array_key_exists(Config::BASE_CONFIG_KEY, $column);
                $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);
            }
        }

        return $result;
    }

    protected function getDefaultEditorView(): string
    {
        return 'oroform/js/app/views/editor/multi-checkbox-editor-view';
    }
}
