<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validation constraint validator that applies Valid validator with explicit validation groups specified in
 * "embeddedGroups" option.
 */
class ValidEmbeddableValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     * @param ValidEmbeddable $constraint
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidEmbeddable) {
            throw new UnexpectedTypeException($constraint, ValidEmbeddable::class);
        }

        if (null === $value) {
            return;
        }

        $this->context
            ->getValidator()
            ->inContext($this->context)
            ->validate($value, null, ValidationGroupUtils::resolveValidationGroups($constraint->embeddedGroups));
    }
}
