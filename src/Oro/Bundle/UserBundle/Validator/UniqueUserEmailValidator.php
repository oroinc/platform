<?php

namespace Oro\Bundle\UserBundle\Validator;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Validator\Constraints\UniqueUserEmail;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that User entity has unique email.
 */
class UniqueUserEmailValidator extends ConstraintValidator
{
    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param User $entity
     * @param UniqueUserEmail $constraint
     *
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        $email = $entity->getEmail();
        if (!$email) {
            return;
        }

        /** @var User $existingUser */
        $existingUser = $this->userManager->findUserByEmail($email);
        if (null !== $existingUser && $existingUser->getId() !== $entity->getId()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('email')
                ->setInvalidValue($entity->getEmail())
                ->addViolation();
        }
    }
}
