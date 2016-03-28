<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
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
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, %s given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $className  = $value->getEntity()->getClassName();
        $fieldName  = $value->getFieldName();
        $type       = $value->getType();
        $getterName = $this->methodNameChecker->getGetters($className, $fieldName);

        if (strlen($getterName) > 0) {
            $this->addViolation($constraint->message, $getterName, '');
        }

        $settersName = $this->methodNameChecker->getSetters($className, $fieldName);

        if (strlen($settersName) > 0) {
            $this->addViolation($constraint->message, $settersName, '');
        }

        if (in_array($type, RelationType::$anyToAnyRelations, false)) {
            $relationMethodsName = $this->methodNameChecker->getRelationMethods($className, $fieldName);

            if (strlen($relationMethodsName) > 0) {
                $this->addViolation($constraint->message, $relationMethodsName, '');
            }
        }
    }
}
