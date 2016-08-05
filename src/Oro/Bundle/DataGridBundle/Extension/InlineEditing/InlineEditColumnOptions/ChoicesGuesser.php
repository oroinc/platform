<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper;

/**
 * Class ChoicesGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class ChoicesGuesser implements GuesserInterface
{
    /** Frontend type */
    const SELECT = 'select';
    const DEFAULT_EDITOR_VIEW = 'oroform/js/app/views/editor/select-editor-view';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ChoiceFieldHelper */
    protected $choiceHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ChoiceFieldHelper $choiceHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, ChoiceFieldHelper $choiceHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->choiceHelper = $choiceHelper;
    }

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
            if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                if ($isEnabledInline) {
                    $result[Configuration::BASE_CONFIG_KEY] = [Configuration::CONFIG_ENABLE_KEY => true];
                }
                $result[PropertyInterface::FRONTEND_TYPE_KEY] = self::SELECT;
                $result[PropertyInterface::TYPE_KEY] = 'field';

                $targetEntity = $metadata->getAssociationTargetClass($columnName);
                $targetEntityMetadata = $entityManager->getClassMetadata($targetEntity);
                $labelField = $this->getLabelField($columnName, $column, $targetEntityMetadata);
                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();
                if (empty($column[Configuration::CHOICES_KEY])) {
                    $result[Configuration::CHOICES_KEY] = $this->choiceHelper
                        ->getChoices($targetEntity, $keyField, $labelField);
                }

                $isConfiguredInlineEdit = array_key_exists(Configuration::BASE_CONFIG_KEY, $column);
                $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result);
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
        if (!$this->isConfiguredViewEditor($column, $isConfiguredInlineEdit)) {
            $result[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY][Configuration::VIEW_KEY]
                = static::DEFAULT_EDITOR_VIEW;
        }

        return $result;
    }

    /**
     * @param $column
     * @param $isConfiguredInlineEdit
     *
     * @return bool
     */
    protected function isConfiguredViewEditor($column, $isConfiguredInlineEdit)
    {
        $isConfigured = $isConfiguredInlineEdit
            && array_key_exists(Configuration::EDITOR_KEY, $column[Configuration::BASE_CONFIG_KEY]);
        $isConfigured = $isConfigured
            && array_key_exists(
                Configuration::VIEW_KEY,
                $column[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY]
            );

        return $isConfigured;
    }

    /**
     * @param $column
     *
     * @return bool
     */
    protected function isConfiguredAccessor($column)
    {
        $isConfigured = isset($column[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY]);
        $isConfigured = $isConfigured
            || isset($column[Configuration::BASE_CONFIG_KEY][Configuration::AUTOCOMPLETE_API_ACCESSOR_KEY]);

        return $isConfigured;
    }

    /**
     * @param $columnName
     * @param $column
     * @param $targetEntityMetadata
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getLabelField($columnName, $column, $targetEntityMetadata)
    {
        if (isset($column[Configuration::BASE_CONFIG_KEY]
                [Configuration::VIEW_OPTIONS_KEY][Configuration::VALUE_FIELD_NAME_KEY])) {
            $labelField = $column[Configuration::BASE_CONFIG_KEY]
                [Configuration::VIEW_OPTIONS_KEY][Configuration::VALUE_FIELD_NAME_KEY];
        } else {
            $labelField = $this->choiceHelper->guessLabelField($targetEntityMetadata, $columnName);
        }

        return $labelField;
    }
}
