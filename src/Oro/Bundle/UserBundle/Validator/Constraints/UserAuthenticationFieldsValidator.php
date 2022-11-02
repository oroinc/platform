<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that username is not equal to Primary Email for another user.
 */
class UserAuthenticationFieldsValidator extends ConstraintValidator
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$constraint instanceof UserAuthenticationFields) {
            throw new UnexpectedTypeException($constraint, UserAuthenticationFields::class);
        }
        if (!$entity instanceof User) {
            throw new UnexpectedTypeException($entity, User::class);
        }

        // Don't allow saving username value if such value exist as Primary Email for another user
        if ($entity->getUsername()
            && filter_var($entity->getUsername(), FILTER_VALIDATE_EMAIL)
            && $entity->getUsername() !== $entity->getEmail()
        ) {
            /** @var User $user */
            $user = $this->isSameUserExists($entity);
            if ($user) {
                $this->context->buildViolation($constraint->message, [])
                    ->atPath('username')
                    ->addViolation();
            }
        }
    }

    /**
     * Find existing user with Primary Email as current User's Username
     *
     * Example:
     *    Current User:
     *        Username = username@example.com
     *        Email    = jack@example.com
     *    Existing User:
     *        Username = username
     *        Email    = username@example.com
     */
    private function isSameUserExists(User $entity): bool
    {
        $username = $entity->getUsername();
        if (!$username) {
            return false;
        }

        /** @var User $existingUser */
        $existingUser = $this->userManager->findUserByEmail($entity->getUsername());
        if ($existingUser && (!$entity->getId() || $existingUser->getId() !== $entity->getId())) {
            return true;
        }

        return false;
    }
}
