<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Util\UniqueFieldNameHelper;

/**
 * Validates field name for uniqueness. When generating setter and getter methods, characters `_` and `-` are removed
 * and as result e.g for names `id` and `i_d` methods names are identical.
 */
class UniqueExtendEntityFieldValidator extends ConstraintValidator
{
    /** @var UniqueFieldNameHelper */
    protected $uniqueFieldNameHelper;

    /**
     * @param UniqueFieldNameHelper $uniqueFieldNameHelper
     */
    public function __construct(UniqueFieldNameHelper $uniqueFieldNameHelper)
    {
        $this->uniqueFieldNameHelper = $uniqueFieldNameHelper;
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

        // Need hardcoded check for `id` field.
        if (strtolower(Inflector::classify(($fieldName))) === 'id') {
            $this->addViolation($constraint);

            return;
        }

        if (!$this->uniqueFieldNameHelper->isFieldNameUnique($className, $fieldName)) {
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
