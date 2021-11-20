<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Validator\Constraints\UserWithoutRole;
use Oro\Bundle\UserBundle\Validator\Constraints\UserWithoutRoleValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UserWithoutRoleValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new UserWithoutRoleValidator();
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', $this->createMock(Constraint::class));
    }

    public function testValueIsNotCollection()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new UserWithoutRole());
    }

    public function testEmptyUserCollection()
    {
        $constraint = new UserWithoutRole();
        $this->validator->validate(new ArrayCollection(), $constraint);

        $this->assertNoViolation();
    }

    public function testUserWithRoles()
    {
        $user = new User();
        $user->addUserRole(new Role());

        $constraint = new UserWithoutRole();
        $this->validator->validate(new ArrayCollection([$user]), $constraint);

        $this->assertNoViolation();
    }

    public function testUserWithoutRoles()
    {
        $user = (new User())
            ->setFirstName('John')
            ->setLastName('Doe');

        $constraint = new UserWithoutRole();
        $this->validator->validate(new ArrayCollection([$user]), $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ userName }}', 'John Doe')
            ->assertRaised();
    }
}
