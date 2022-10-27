<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that all specified users have at least one role.
 */
class UserWithoutRoleValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UserWithoutRole) {
            throw new UnexpectedTypeException($constraint, UserWithoutRole::class);
        }

        if (!$value instanceof Collection) {
            throw new UnexpectedTypeException($value, Collection::class);
        }

        if ($value->isEmpty()) {
            return;
        }

        $invalidUserNames = [];
        foreach ($value as $user) {
            if (!$user instanceof User) {
                throw new UnexpectedTypeException($user, User::class);
            }
            if (!$user->getRoles()) {
                $invalidUserNames[] = $user->getFullName();
            }
        }

        if ($invalidUserNames) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ userName }}' => implode(', ', $invalidUserNames)]
            );
        }
    }
}
