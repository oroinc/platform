<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides common functionality for validators of entity field configuration.
 *
 * This base class implements helper methods for validating {@see FieldConfigModel} instances,
 * including type checking and violation reporting. It provides access to the field name validation helper
 * for checking field name conflicts and restrictions.
 * Subclasses should extend this to implement specific field configuration validation rules.
 */
abstract class AbstractFieldValidator extends ConstraintValidator
{
    /** @var FieldNameValidationHelper */
    protected $validationHelper;

    public function __construct(FieldNameValidationHelper $validationHelper)
    {
        $this->validationHelper = $validationHelper;
    }

    /**
     * @param mixed $value
     */
    protected function assertValidatingValue($value)
    {
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, %s given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }
    }

    /**
     * @param string $message
     * @param string $newFieldName
     * @param string $existingFieldName
     */
    protected function addViolation($message, $newFieldName, $existingFieldName)
    {
        $this->context->buildViolation($message, ['{{ value }}' => $newFieldName, '{{ field }}' => $existingFieldName])
            ->atPath('fieldName')
            ->addViolation();
    }
}
