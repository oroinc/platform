<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Common\Util\ClassUtils;
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
     * @param string                             $value
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
            $user = $this->findExistingUser($entity);
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
     * @return User|null
     */
    protected function findExistingUser($entity)
    {
        $class = ClassUtils::getClass($entity);
        /** @var UserRepository $repository */
        $repository = $this->registry->getManagerForClass($class)->getRepository($class);

        $qb = $repository->createQueryBuilder('u');
        $qb
            ->andWhere('u.email = :email')
            ->setParameter('email', $entity->getUsername());

        if ($entity->getId()) {
            $qb
                ->andWhere('u.id <> :id')
                ->setParameter('id', $entity->getId());
        }

        $result = $qb
            ->getQuery()
            ->getResult();

        if (count($result) > 0) {
            return $result;
        }

        return null;
    }
}
