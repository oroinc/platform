<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\AbstractFieldConfigBasedValidationLoader;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Loads validation metadata for configurable entity fields.
 */
class ExtendFieldValidationLoader extends AbstractFieldConfigBasedValidationLoader
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     * @param ConfigProvider $fieldConfigProvider
     */
    public function __construct(
        ConfigProvider $extendConfigProvider,
        ConfigProvider $fieldConfigProvider
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->fieldConfigProvider   = $fieldConfigProvider;
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

        if (!$this->isApplicable($metadata->getClassName(), $fieldName)) {
            return;
        }

        $constraints = $this->getConstraintsByFieldType($fieldConfigId->getFieldType());
        foreach ($constraints as $constraint) {
            $metadata->addPropertyConstraint($fieldName, $constraint);
        }
    }

    /**
     * Check if field applicable to add constraint
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isApplicable($className, $fieldName)
    {
        $extendConfig = $this->extendConfigProvider->getConfig($className, $fieldName);

        return !$extendConfig->is('is_deleted') &&
        !$extendConfig->is('state', ExtendScope::STATE_NEW) &&
        $extendConfig->is('is_extend');
    }
}
