<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Validates method name for uniqueness for field name.
 */
class UniqueExtendEntityMethodNameValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_extend_entity_method_name';

    /** @var ClassMethodNameChecker */
    protected $methodNameChecker;

    /**
     * @param FieldNameValidationHelper $validationHelper
     * @param ClassMethodNameChecker    $methodNameChecker
     *
     */
    public function __construct(FieldNameValidationHelper $validationHelper, ClassMethodNameChecker $methodNameChecker)
    {
        parent::__construct($validationHelper);

        $this->methodNameChecker = $methodNameChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $this->assertValidatingValue($value);

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
}
