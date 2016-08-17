<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Validator\Constraints\UserAuthenticationFieldsConstraint;

class UserAuthenticationFieldsValidator extends ConstraintValidator
{
    const VIOLATION_PATH = 'username';
    const ALIAS          = 'oro_user.validator.user_authentication_fields';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
     * @param User $entity
     *
     * @return bool
     */
    protected function isSameUserExists(User $entity)
    {
        /** @var UserRepository $repository */
        $repository = $this->registry->getManagerForClass(User::class)->getRepository(User::class);

        $result = $repository->findUsersWithEmailAsUsername($entity->getUsername(), $entity->getId());

        if (count($result) > 0) {
            return true;
        }

        return false;
    }
}
