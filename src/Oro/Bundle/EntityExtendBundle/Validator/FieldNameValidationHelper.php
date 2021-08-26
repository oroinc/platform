<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Event\ValidateBeforeRemoveFieldEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provide additional methods for config field name validation
 */
class FieldNameValidationHelper
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var NewEntitiesHelper */
    protected $newEntitiesHelper;
    private Inflector $inflector;

    public function __construct(
        ConfigProvider $extendConfigProvider,
        EventDispatcherInterface $eventDispatcher,
        NewEntitiesHelper $newEntitiesHelper,
        Inflector $inflector
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->newEntitiesHelper = $newEntitiesHelper;
        $this->inflector = $inflector;
    }

    /**
     * Checks whether a field can be restored.
     * Unessential symbols, like _ or upper case letters, in a field name are ignored.
     */
    public function canFieldBeRestored(FieldConfigModel $field): bool
    {
        $normalizedFieldName = $this->normalizeFieldName($field->getFieldName());

        $configs = $this->extendConfigProvider->getConfigs($field->getEntity()->getClassName(), true);
        foreach ($configs as $config) {
            /** @var FieldConfigId $configId */
            $configId  = $config->getId();
            $fieldName = $configId->getFieldName();

            if ($field->getFieldName() === $fieldName) {
                // skip current field
                continue;
            }

            if ($normalizedFieldName === $this->normalizeFieldName($fieldName)
                && !$config->is('is_deleted')
                && !$config->is('state', ExtendScope::STATE_DELETE)
            ) {
                // an active field with similar name exists
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether a field can be removed.
     * Return empty array when can remove otherwise array with validation errors
     */
    public function getRemoveFieldValidationErrors(FieldConfigModel $field): array
    {
        $event = new ValidateBeforeRemoveFieldEvent($field);
        $this->eventDispatcher->dispatch($event, ValidateBeforeRemoveFieldEvent::NAME);

        return $event->getValidationMessages();
    }

    /**
     * Finds a field by its name.
     * Unessential symbols, like _ or upper case letters, in a field name are ignored.
     */
    public function findFieldConfig(string $className, string $fieldName, bool $isAttribute = false): ?ConfigInterface
    {
        $fieldConfig = null;

        $normalizedFieldName = $this->normalizeFieldName($fieldName);

        $configs = $this->extendConfigProvider->getConfigs($className, true);
        foreach ($configs as $config) {
            if ($normalizedFieldName === $this->normalizeFieldName($config->getId()->getFieldName())) {
                $fieldConfig = $config;
                break;
            }
        }

        return $fieldConfig;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param bool $isAttribute
     * @return array [<fieldsName>, <fieldType>] will be returned in case field found, empty array otherwise
     */
    public function getSimilarExistingFieldData(string $className, string $fieldName, bool $isAttribute = false): array
    {
        $fieldConfig = $this->findFieldConfig($className, $fieldName);
        if ($fieldConfig
            && $this->hasFieldNameConflict($fieldName, $fieldConfig, $fieldConfig->getId()->getFieldName())
        ) {
            /** @var FieldConfigId $id */
            $id = $fieldConfig->getId();

            return [$id->getFieldName(), $id->getFieldType()];
        }

        /** @var FieldConfigModel $existField */
        $existField = $this->newEntitiesHelper->getEntity($this->getKey($className, $fieldName));

        return $existField ? [$existField->getFieldName(), $existField->getType()] : [];
    }

    public function registerField(FieldConfigModel $fieldConfigModel)
    {
        $key = $this->getKey($fieldConfigModel->getEntity()->getClassName(), $fieldConfigModel->getFieldName());

        $this->newEntitiesHelper->setEntity($key, $fieldConfigModel);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    protected function getKey($className, $fieldName)
    {
        return sprintf(
            '%s|%s|%s',
            FieldConfigModel::class,
            $className,
            $this->normalizeFieldName($fieldName)
        );
    }

    /**
     * Checks whether the name of a new field conflicts with the name of existing field.
     *
     * @param string $newFieldName
     * @param ConfigInterface $existingFieldConfig
     * @param string $existingFieldName
     *
     * @return bool
     */
    protected function hasFieldNameConflict(
        $newFieldName,
        ConfigInterface $existingFieldConfig,
        string $existingFieldName
    ): bool {
        if (strtolower($newFieldName) === strtolower($existingFieldName)) {
            return true;
        }
        if ($this->normalizeFieldName($newFieldName) === $this->normalizeFieldName($existingFieldName)
            && !$existingFieldConfig->is('is_deleted')
            && !$existingFieldConfig->is('state', ExtendScope::STATE_DELETE)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Normalizes the given field name.
     * The normalized name is lower cased and unessential symbols, like _, are removed.
     */
    public function normalizeFieldName(string $fieldName): string
    {
        return strtolower($this->inflector->classify($fieldName));
    }
}
