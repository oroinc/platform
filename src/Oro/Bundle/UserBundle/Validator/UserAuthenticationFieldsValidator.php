<?php

namespace Oro\Bundle\UserBundle\Validator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Validator\Constraints\UserAuthenticationFieldsConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validates that username is not equal to Primary Email for another user.
 */
class UserAuthenticationFieldsValidator extends ConstraintValidator
{
    const VIOLATION_PATH = 'username';
    const ALIAS          = 'oro_user.validator.user_authentication_fields';

    /**
     * @var UserManager
     */
    protected $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param User                               $entity
     * @param UserAuthenticationFieldsConstraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof User) {
            return;
        }

        // Don't allow saving username value if such value exist as Primary Email for another user
        if ($entity->getUsername()
            && filter_var($entity->getUsername(), FILTER_VALIDATE_EMAIL)
            && $entity->getUsername() !== $entity->getEmail()
        ) {
            /** @var User $user */
            $user = $this->isSameUserExists($entity);
            if ($user) {
                /** @var ExecutionContextInterface $context */
                $context = $this->context;
                $context->buildViolation($constraint->message, [])
                    ->atPath(self::VIOLATION_PATH)
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
     *
     * @param User $entity
     *
     * @return bool
     */
    protected function isSameUserExists(User $entity)
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
