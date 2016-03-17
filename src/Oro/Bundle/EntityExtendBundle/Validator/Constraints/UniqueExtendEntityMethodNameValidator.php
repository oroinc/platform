<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassChecking;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Validates method name for uniqueness for field name.
 */
class UniqueExtendEntityMethodNameValidator extends ConstraintValidator
{
    const ALIAS = 'oro_entity_extend.validator.unique_extend_entity_method_name';

    /** @var ExtendClassChecking */
    protected $extendClassChecking;

    /**
     * @param ExtendClassChecking $extendClassChecking
     *
     */
    public function __construct(ExtendClassChecking $extendClassChecking)
    {
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

        if ($this->extendClassChecking->hasGetter($className, $fieldName)
            || $this->extendClassChecking->hasSetter($className, $fieldName)
            || $this->extendClassChecking->hasRemover($className, $fieldName)
        ) {
            $this->addViolation($constraint);
        }
    }

    /**
     * @param Constraint $constraint
     */
    protected function addViolation(Constraint $constraint)
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context->buildViolation($constraint->message)
            ->atPath($constraint->path)
            ->addViolation();
    }
}
