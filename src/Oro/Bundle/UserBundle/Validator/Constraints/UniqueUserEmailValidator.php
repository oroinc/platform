<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that User entity has unique email.
 */
class UniqueUserEmailValidator extends ConstraintValidator
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof UniqueUserEmail) {
            throw new UnexpectedTypeException($constraint, UniqueUserEmail::class);
        }
        if (!$value instanceof User) {
            throw new UnexpectedTypeException($value, User::class);
        }

        $email = $value->getEmail();
        if (!$email) {
            return;
        }

        /** @var User $existingUser */
        $existingUser = $this->userManager->findUserByEmail($email);
        if (null !== $existingUser && $existingUser->getId() !== $value->getId()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('email')
                ->setInvalidValue($value->getEmail())
                ->addViolation();
        }
    }
}
