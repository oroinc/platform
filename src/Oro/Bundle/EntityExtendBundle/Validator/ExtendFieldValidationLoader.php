<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\AbstractFieldConfigBasedValidationLoader;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Loads validation metadata for configurable entity fields.
 */
class ExtendFieldValidationLoader extends AbstractFieldConfigBasedValidationLoader
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    private FieldConfigConstraintsFactory $fieldConfigConstraintsFactory;

    public function __construct(
        ConfigProvider $extendConfigProvider,
        ConfigProvider $fieldConfigProvider,
        FieldConfigConstraintsFactory $fieldConfigConstraintsFactory
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->fieldConfigProvider = $fieldConfigProvider;
        $this->fieldConfigConstraintsFactory = $fieldConfigConstraintsFactory;
    }

    /**
     * {@inheritDoc}
     */
    protected function processFieldConfig(ClassMetadata $metadata, ConfigInterface $fieldConfig): void
    {
        if (!$fieldConfig->is('is_enabled')) {
            return;
        }

        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $fieldConfig->getId();
        $fieldName     = $fieldConfigId->getFieldName();

        $extendConfig = $this->extendConfigProvider->getConfig($metadata->getClassName(), $fieldName);
        if (!$this->isApplicable($extendConfig)) {
            return;
        }

        $fieldType = $fieldConfigId->getFieldType();
        $constraints = \array_merge(
            $this->getConstraintsByFieldType($fieldType),
            $this->fieldConfigConstraintsFactory->create($extendConfig)
        );

        foreach ($constraints as $constraint) {
            $metadata->addPropertyConstraint($fieldName, $constraint);
        }
    }

    /**
     * Check if field applicable to add constraint
     */
    protected function isApplicable(ConfigInterface $extendConfig): bool
    {
        return !$extendConfig->is('is_deleted') &&
            !$extendConfig->is('state', ExtendScope::STATE_NEW) &&
            $extendConfig->is('is_extend');
    }
}
