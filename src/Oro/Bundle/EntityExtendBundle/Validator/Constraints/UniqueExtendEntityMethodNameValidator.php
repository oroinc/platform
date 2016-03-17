<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassChecking;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Validates method name for uniqueness for field name.
 */
class UniqueExtendEntityMethodNameValidator extends AbstractFieldValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_extend_entity_method_name';

    /** @var ExtendClassChecking */
    protected $extendClassChecking;

    /**
     * @param FieldNameValidationHelper $validationHelper
     * @param ExtendClassChecking       $extendClassChecking
     *
     */
    public function __construct(FieldNameValidationHelper $validationHelper, ExtendClassChecking $extendClassChecking)
    {
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

        $className = $value->getEntity()->getClassName();
        $fieldName = $value->getFieldName();

        if ($this->extendClassChecking->hasGetter($className, $fieldName)) {
            $this->addViolation($constraint->message, 'getters', $className);
        }

        if ($this->extendClassChecking->hasSetter($className, $fieldName)) {
            $this->addViolation($constraint->message, 'setters', $className);
        }

        if ($this->extendClassChecking->hasRemover($className, $fieldName)) {
            $this->addViolation($constraint->message, 'remover', $className);
        }
    }
}
