<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Validates method name for uniqueness for field name.
 */
class UniqueExtendEntityMethodNameValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_extend_entity_method_name';

    /** @var ClassMethodNameChecker */
    protected $extendClassChecking;

    /**
     * @param FieldNameValidationHelper $validationHelper
     * @param ClassMethodNameChecker    $extendClassChecking
     *
     */
    public function __construct(
        FieldNameValidationHelper $validationHelper,
        ClassMethodNameChecker $extendClassChecking
    ) {
        parent::__construct($validationHelper);

        $this->extendClassChecking = $extendClassChecking;
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
        $methodName = $this->extendClassChecking->getConflictMethodName($className, $fieldName);

        if (!empty($methodName)) {
            $this->addViolation($constraint->message, $methodName, '');
        }
    }
}
