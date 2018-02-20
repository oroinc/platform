<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Validator\Constraints\UserWithoutRole;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserWithoutRoleValidator extends ConstraintValidator
{
    const ALIAS = 'oro_user.validator.user_without_role';

    /**
     * @param mixed $users
     * @param UserWithoutRole $constraint
     * @throws UnexpectedTypeException
     *
     * {@inheritdoc}
     */
    public function validate($users, Constraint $constraint)
    {
        if (!$users instanceof Collection) {
            throw new UnexpectedTypeException($users, Collection::class);
        }

        if ($users->isEmpty()) {
            return;
        }

        $invalidUserNames = [];
        foreach ($users as $user) {
            if (!$this->isUserValid($user)) {
                $invalidUserNames[] = $this->getUserFullName($user);
            }
        }

        if ($invalidUserNames) {
            $customerNames = implode(', ', $invalidUserNames);
            $this->context->addViolation($constraint->message, ['{{ userName }}' => $customerNames]);
        }
    }

    /**
     * @param FirstNameInterface|LastNameInterface $user
     * @return string
     * @throws UnexpectedTypeException
     */
    private function getUserFullName($user)
    {
        if (!$user instanceof FirstNameInterface) {
            throw new UnexpectedTypeException($user, FirstNameInterface::class);
        }
        if (!$user instanceof LastNameInterface) {
            throw new UnexpectedTypeException($user, LastNameInterface::class);
        }
        return sprintf('%s %s', $user->getFirstName(), $user->getLastName());
    }

    protected function isUserValid(UserInterface $user)
    {
        return (bool)$user->getRoles();
    }
}
