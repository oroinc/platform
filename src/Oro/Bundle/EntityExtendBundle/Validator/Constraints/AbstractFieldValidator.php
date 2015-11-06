<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;

abstract class AbstractFieldValidator extends ConstraintValidator
{
    /** @var FieldNameValidationHelper */
    protected $validationHelper;

    /**
     * @param FieldNameValidationHelper $validationHelper
     */
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
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context
            ->buildViolation(
                $message,
                ['{{ value }}' => $newFieldName, '{{ field }}' => $existingFieldName]
            )
            ->atPath('fieldName')
            ->addViolation();
    }
}
