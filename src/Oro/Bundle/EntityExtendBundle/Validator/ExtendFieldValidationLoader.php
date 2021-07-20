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

    /** @var FieldConfigConstraintsFactory */
    private $fieldConfigConstraintsFactory;

    public function __construct(
        ConfigProvider $extendConfigProvider,
        ConfigProvider $fieldConfigProvider
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->fieldConfigProvider   = $fieldConfigProvider;
    }

    public function setFieldConfigConstraintsFactory(FieldConfigConstraintsFactory $fieldConfigConstraintsFactory): void
    {
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

        if (!$this->isApplicable($metadata->getClassName(), $fieldName)) {
            return;
        }

        $constraints = $this->getConstraintsByFieldType($fieldConfigId->getFieldType());
        if ($this->fieldConfigConstraintsFactory) {
            $extendConfig = $this->extendConfigProvider->getConfig($metadata->getClassName(), $fieldName);
            $constraints = \array_merge(
                $constraints,
                $this->fieldConfigConstraintsFactory->create($extendConfig)
            );
        }
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
