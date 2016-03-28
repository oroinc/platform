<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validates method name for uniqueness for field name.
 */
class UniqueExtendEntityMethodNameValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_extend_entity_method_name';

    /** @var ClassMethodNameChecker */
    protected $methodNameChecker;

    /**
     * @param ClassMethodNameChecker    $methodNameChecker
     */
    public function __construct(ClassMethodNameChecker $methodNameChecker)
    {
        $this->methodNameChecker = $methodNameChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, %s given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $className = $value->getEntity()->getClassName();
        if (!class_exists($className)) {
            return;
        }
        $fieldName = $value->getFieldName();
        $type      = $value->getType();
        $getters   = $this->methodNameChecker
            ->getMethods($fieldName, $className, ClassMethodNameChecker::$getters);
        $setters = $this->methodNameChecker
            ->getMethods($fieldName, $className, ClassMethodNameChecker::$setters);
        $methods = array_merge($getters, $setters);
        if (in_array($type, RelationType::$anyToAnyRelations, false)) {
            $relationMethods = $this->methodNameChecker
                ->getMethods($fieldName, $className, ClassMethodNameChecker::$relationMethods);
            $methods = array_merge($methods, $relationMethods);
        }
        if (!empty($methods)) {
            $this->addViolation($constraint->message, implode(', ', $methods), '');
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
