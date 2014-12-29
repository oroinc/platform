<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class UniqueEnumNameValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_enum_name';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string)$value;

        $enumCode = ExtendHelper::buildEnumCode($value, false);
        if (empty($enumCode)) {
            return;
        }

        if ($this->isExistingEnum($enumCode, $constraint->entityClassName, $constraint->fieldName)) {
            $this->context->addViolation($constraint->message, array('{{ value }}' => $value));
        }
    }

    /**
     * Checks if an enum with the given code already exist
     *
     * @param string $enumCode
     * @param string $entityClassName
     * @param string $fieldName
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function isExistingEnum($enumCode, $entityClassName, $fieldName)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $enumConfigProvider   = $this->configManager->getProvider('enum');

        // at first check if an enum entity with the given code is already exist
        $entityConfigs = $extendConfigProvider->getConfigs(null, true);
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS)) {
                continue;
            }
            $enumEntityConfig = $enumConfigProvider->getConfig($entityConfig->getId()->getClassName());
            if ($enumCode === $enumEntityConfig->get('code')) {
                return true;
            }
        }

        // if an enum entity with the given code was not found than check if there is new field with
        // the given enum code
        $entityConfigs = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend')) {
                continue;
            }
            if (!$entityConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATE])) {
                continue;
            }

            $fieldConfigs = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                if ($fieldConfigId->getFieldName() === $fieldName
                    && $fieldConfigId->getClassName() === $entityClassName
                ) {
                    // ignore a field for which the validation was called
                    continue;
                }
                if (!in_array($fieldConfigId->getFieldType(), ['enum', 'multiEnum'])) {
                    continue;
                }
                if (!$fieldConfig->in('state', [ExtendScope::STATE_NEW])) {
                    continue;
                }

                $enumFieldConfig  = $enumConfigProvider->getConfig(
                    $fieldConfigId->getClassName(),
                    $fieldConfigId->getFieldName()
                );
                $existingEnumName = $enumFieldConfig->get('enum_name');
                if (!empty($existingEnumName) && $enumCode === ExtendHelper::buildEnumCode($existingEnumName)) {
                    return true;
                }
            }
        }

        return false;
    }
}
