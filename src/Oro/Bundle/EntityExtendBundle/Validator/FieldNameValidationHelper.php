<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Event\ValidateBeforeRemoveFieldEvent;

class FieldNameValidationHelper
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param ConfigProvider $extendConfigProvider
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ConfigProvider $extendConfigProvider, EventDispatcherInterface $eventDispatcher)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Checks whether a field can be restored.
     * Unessential symbols, like _ or upper case letters, in a field name are ignored.
     *
     * @param FieldConfigModel $field
     *
     * @return bool
     */
    public function canFieldBeRestored(FieldConfigModel $field)
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
     *
     * @param FieldConfigModel $field
     *
     * @return array
     */
    public function getRemoveFieldValidationErrors(FieldConfigModel $field)
    {
        $event = new ValidateBeforeRemoveFieldEvent($field);
        $this->eventDispatcher->dispatch(ValidateBeforeRemoveFieldEvent::NAME, $event);

        return $event->getValidationMessages();
    }

    /**
     * Finds a field by its name.
     * Unessential symbols, like _ or upper case letters, in a field name are ignored.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return Config|null
     */
    public function findExtendFieldConfig($className, $fieldName)
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
     * Checks whether the name of a new field conflicts with the name of existing field.
     *
     * @param string $newFieldName
     * @param Config $existingFieldConfig
     *
     * @return bool
     */
    public function hasFieldNameConflict($newFieldName, Config $existingFieldConfig)
    {
        $existingFieldName = $existingFieldConfig->getId()->getFieldName();
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
     * Normalizes a field name.
     * The normalized name is lower cased and unessential symbols, like _, are removed.
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function normalizeFieldName($fieldName)
    {
        return strtolower(Inflector::classify($fieldName));
    }
}
