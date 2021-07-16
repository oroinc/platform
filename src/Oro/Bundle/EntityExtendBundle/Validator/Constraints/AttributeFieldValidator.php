<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * This validator checks if a field is an attribute.
 */
class AttributeFieldValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_entity_extend.validator.attribute_field';

    /**
     * @var FieldNameValidationHelper
     */
    protected $validationHelper;

    /**
     * @var ConfigProvider
     */
    protected $attributeConfigProvider;

    public function __construct(FieldNameValidationHelper $validationHelper, ConfigProvider $attributeConfigProvider)
    {
        $this->validationHelper = $validationHelper;
        $this->attributeConfigProvider = $attributeConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Only %s is supported, %s is given',
                    FieldConfigModel::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $fieldName = $this->validationHelper->normalizeFieldName($value->getFieldName());
        $configs = $this->attributeConfigProvider->getConfigs($value->getEntity()->getClassName(), true);
        $fieldConfig = null;
        foreach ($configs as $config) {
            if ($fieldName === $this->validationHelper->normalizeFieldName($config->getId()->getFieldName())) {
                $fieldConfig = $config;
                break;
            }
        }

        if ($fieldConfig && !$fieldConfig->get('is_attribute')) {
            $this->context
                ->buildViolation(
                    $constraint->message,
                    ['{{ field }}' => $value->getFieldName()]
                )
                ->atPath('fieldName')
                ->addViolation();
        }
    }
}
