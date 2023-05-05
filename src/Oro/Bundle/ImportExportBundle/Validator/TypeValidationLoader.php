<?php

namespace Oro\Bundle\ImportExportBundle\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\AbstractFieldConfigBasedValidationLoader;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Loads validation metadata for entity fields importing
 */
class TypeValidationLoader extends AbstractFieldConfigBasedValidationLoader
{
    /** @var string */
    public const IMPORT_FIELD_TYPE_VALIDATION_GROUP = 'import_field_type';

    /** @var ConfigProvider */
    private $extendConfigProvider;

    public function __construct(ConfigProvider $extendConfigProvider, ConfigProvider $fieldConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->fieldConfigProvider = $fieldConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function processFieldConfig(ClassMetadata $metadata, ConfigInterface $fieldConfig): void
    {
        if (!$this->isApplicable($metadata, $fieldConfig)) {
            return;
        }

        $fieldConfigId = $fieldConfig->getId();
        $fieldName = $fieldConfigId->getFieldName();

        $constraints = $this->getConstraintsByFieldType($fieldConfigId->getFieldType());
        foreach ($constraints as $constraint) {
            $metadata->addPropertyConstraint($fieldName, $constraint);
        }
    }

    /**
     * Check if field applicable to add constraint
     */
    private function isApplicable(ClassMetadata $metadata, ConfigInterface $fieldConfig): bool
    {
        $className = $metadata->getClassName();
        $fieldName = $fieldConfig->getId()
            ->getFieldName();

        if (!EntityPropertyInfo::propertyExists($className, $fieldName) || $fieldConfig->is('excluded')) {
            return false;
        }

        $extendConfig = $this->extendConfigProvider->getConfig($className, $fieldName);

        return !$extendConfig->is('is_deleted')
            && $extendConfig->is('state', ExtendScope::STATE_ACTIVE);
    }
}
