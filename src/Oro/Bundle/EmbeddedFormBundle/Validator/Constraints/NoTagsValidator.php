<?php

namespace Oro\Bundle\EmbeddedFormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for the NoTags constraint.
 *
 * This validator checks that a value does not contain HTML tags by comparing the original
 * value with its stripped version (using PHP's `strip_tags` function). If HTML tags are detected,
 * it adds a violation to the validation context with the constraint's error message.
 */
class NoTagsValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = trim((string)$value);
        $strippedValue = strip_tags($value);

        if ($value !== $strippedValue) {
            $this->context->addViolation($constraint->message);
        }
    }
}
