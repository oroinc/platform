<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration as Config;
use Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Guesses options for "select" columns.
 */
class ChoicesGuesser implements GuesserInterface
{
    protected DoctrineHelper $doctrineHelper;
    protected ChoiceFieldHelper $choiceHelper;

    public function __construct(DoctrineHelper $doctrineHelper, ChoiceFieldHelper $choiceHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->choiceHelper = $choiceHelper;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function guessColumnOptions($columnName, $entityName, $column, $isEnabledInline = false)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $metadata = $entityManager->getClassMetadata($entityName);

        $result = [];

        if (!$this->isConfiguredAccessor($column) && $metadata->hasAssociation($columnName)) {
            $mapping = $metadata->getAssociationMapping($columnName);
            if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                if ($isEnabledInline) {
                    $result[Config::BASE_CONFIG_KEY] = [Config::CONFIG_ENABLE_KEY => true];
                }
                $result[PropertyInterface::FRONTEND_TYPE_KEY] = 'select';
                $result[PropertyInterface::TYPE_KEY] = 'field';

                $targetEntity = $metadata->getAssociationTargetClass($columnName);
                $targetEntityMetadata = $entityManager->getClassMetadata($targetEntity);
                $labelField = $this->getLabelField($columnName, $column, $targetEntityMetadata);
                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();

                if (empty($column[Config::CHOICES_KEY])) {
                    $translatable = isset($column['translatable']) && $column['translatable'] === true;
                    $result[Config::CHOICES_KEY] = $this->choiceHelper
                        ->getChoices($targetEntity, $keyField, $labelField, null, $translatable);
                    if ($translatable) {
                        $result['translatable_options'] = false;
                    }
                }

                if (\array_key_exists(PropertyInterface::DATA_NAME_KEY, $column)
                    && str_contains($column[PropertyInterface::DATA_NAME_KEY], '_target_field')
                ) {
                    $result[PropertyInterface::DATA_NAME_KEY] = $columnName.'_identity';
                }

                $isConfiguredInlineEdit = \array_key_exists(Config::BASE_CONFIG_KEY, $column);
                $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);
            }
        }

        return $result;
    }

    protected function guessEditorView(array $column, bool $isConfiguredInlineEdit, array $result): array
    {
        if (!$this->isConfiguredViewEditor($column, $isConfiguredInlineEdit)) {
            $result[Config::BASE_CONFIG_KEY][Config::EDITOR_KEY][Config::VIEW_KEY]
                = $this->getDefaultEditorView();
        }

        return $result;
    }

    protected function isConfiguredViewEditor(array $column, bool $isConfiguredInlineEdit): bool
    {
        return
            $isConfiguredInlineEdit
            && \array_key_exists(Config::EDITOR_KEY, $column[Config::BASE_CONFIG_KEY])
            && \array_key_exists(Config::VIEW_KEY, $column[Config::BASE_CONFIG_KEY][Config::EDITOR_KEY]);
    }

    protected function getDefaultEditorView(): string
    {
        return 'oroform/js/app/views/editor/select-editor-view';
    }

    protected function isConfiguredAccessor(array $column): bool
    {
        return
            isset($column[Config::BASE_CONFIG_KEY][Config::EDITOR_KEY])
            || isset($column[Config::BASE_CONFIG_KEY][Config::AUTOCOMPLETE_API_ACCESSOR_KEY]);
    }

    protected function getLabelField(string $columnName, array $column, ClassMetadata $targetEntityMetadata): string
    {
        return
            $column[Config::BASE_CONFIG_KEY][Config::VIEW_OPTIONS_KEY][Config::VALUE_FIELD_NAME_KEY]
            ?? $this->choiceHelper->guessLabelField($targetEntityMetadata, $columnName);
    }
}
