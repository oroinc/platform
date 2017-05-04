<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Validator\Constraints\UserWithoutRole;

class UserWithoutRoleValidator extends ConstraintValidator
{
    const ALIAS = 'oro_user.validator.user_without_role';

    /**
     * @param mixed $value
     * @param UserWithoutRole $constraint
     * @throws \InvalidArgumentException
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Collection) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of "%s", "%s" given',
                    Collection::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $users = $value->toArray();

        if (!$users) {
            return;
        }

        $result = true;
        $affectedCustomerNames = [];
        /** @var UserInterface|FirstNameInterface|LastNameInterface $user */
        foreach ($users as $user) {
            $this->checkUserType($user);

            if (!$user->getRoles()) {
                $affectedCustomerNames[] = $this->getUserFullName($user);
                $result = false;
            }
        }

        if ($result === false) {
            $customerNames = implode(', ', $affectedCustomerNames);

            $this->context->addViolation($constraint->message, ['{{ userName }}' => $customerNames]);
        }
    }

    /**
     * @param object $user
     */
    private function checkUserType($user)
    {
        if (!is_object($user)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected object, "%s" given',
                    gettype($user)
                )
            );
        }

        $this->checkClassImplementation($user, UserInterface::class);
        $this->checkClassImplementation($user, FirstNameInterface::class);
        $this->checkClassImplementation($user, LastNameInterface::class);
    }

    /**
     * @param object $user
     * @param string $interface
     */
    private function checkClassImplementation($user, $interface)
    {
        if (!$user instanceof $interface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Class %s has to implement %s',
                    get_class($user),
                    $interface
                )
            );
        }
    }

    /**
     * @param FirstNameInterface|LastNameInterface $user
     * @return string
     */
    private function getUserFullName($user)
    {
        return sprintf('%s %s', $user->getFirstName(), $user->getLastName());
    }
}
