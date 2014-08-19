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

        if ($this->isExistingEnum($value, $constraint->entityClassName, $constraint->fieldName)) {
            $this->context->addViolation($constraint->message, array('{{ value }}' => $value));
        }
    }

    /**
     * Checks id the enum with the given code is already exist
     *
     * @param string $enumName
     * @param string $entityClassName
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isExistingEnum($enumName, $entityClassName, $fieldName)
    {
        $enumCode = ExtendHelper::buildEnumCode($enumName);

        $enumConfigProvider = $this->configManager->getProvider('enum');

        // at first check if an enum entity with the given code is already exist
        $groupingConfigProvider = $this->configManager->getProvider('grouping');
        $entityConfigs          = $groupingConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            $groups = $entityConfig->get('groups', false, []);
            if (!empty($groups)
                && in_array('enum', $groups)
                && $enumConfigProvider->hasConfig($entityConfig->getId()->getClassName())
            ) {
                $enumEntityConfig = $enumConfigProvider->getConfig($entityConfig->getId()->getClassName());
                $existingEnumCode = $enumEntityConfig->get('code');
                if (strcasecmp($enumCode, $existingEnumCode) === 0) {
                    return true;
                }
            }
        }

        // if an enum entity with the given code was not found than check if there is new field with
        // the given enum name
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfigs        = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_UPDATED])) {
                continue;
            }

            $enumFieldConfigs = $enumConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($enumFieldConfigs as $enumFieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $enumFieldConfig->getId();
                if ($fieldConfigId->getFieldName() === $fieldName
                    && $fieldConfigId->getClassName() === $entityClassName
                ) {
                    // ignore a field for which the validation was called
                    continue;
                }
                $existingEnumCode = ExtendHelper::buildEnumCode($enumFieldConfig->get('enum_name'));
                if (strcasecmp($enumCode, $existingEnumCode) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
