<?php

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validate date earlier than.
 */
class DateEarlierThanValidator extends ConstraintValidator
{
    /**
     * @param \DateTime                  $value
     * @param Constraint|DateEarlierThan $constraint
     *
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        $root = $this->context->getRoot();
        if ($root instanceof FormInterface) {
            $valueCompare = $root->has($constraint->field) ? $root->get($constraint->field)->getData() : false;
        } else {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $valueCompare = $propertyAccessor->getValue($root, $constraint->field);
        }
        // values presence should be validated by NotNullValidator
        if (!$value || !$valueCompare) {
            return;
        }
        if (!$value instanceof \DateTime) {
            throw new UnexpectedTypeException($value, 'DateTime');
        }
        if (!$valueCompare instanceof \DateTime) {
            throw new UnexpectedTypeException($valueCompare, 'DateTime');
        }
        if ($value->getTimestamp() > $valueCompare->getTimestamp()) {
            $this->context->addViolation($constraint->message, ['{{ field }}' => $constraint->field]);
        }
    }
}
