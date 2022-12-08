<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a string is a file name without a path.
 */
class FilenameWithoutPathValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof FilenameWithoutPath) {
            throw new UnexpectedTypeException($constraint, FilenameWithoutPath::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (basename($value) !== $value) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
