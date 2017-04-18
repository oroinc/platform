<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Validator\UserWithoutRoleValidator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Validator\Constraints\UserWithoutRole;

class UserWithoutRoleValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UserWithoutRoleValidator
     */
    protected $userWithoutRoleValidator;

    /**
     * @var UserWithoutRole
     */
    protected $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    protected function setUp()
    {
        $this->constraint = new UserWithoutRole();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->userWithoutRoleValidator = new UserWithoutRoleValidator();
        $this->userWithoutRoleValidator->initialize($this->context);
    }

    public function testEmptyUserCollection()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->userWithoutRoleValidator->validate(new ArrayCollection(), $this->constraint);
    }

    public function testUserWithRole()
    {
        $role = $this->getEntity(Role::class);
        $user = $this->getEntity(User::class, ['roles' => [$role]]);

        $userCollection = new ArrayCollection([$user]);

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->userWithoutRoleValidator->validate($userCollection, $this->constraint);
    }

    public function testUserWithoutRoles()
    {
        $user = $this->getEntity(User::class, ['firstName' => 'John', 'lastName' => 'Doe']);

        $userCollection = new ArrayCollection([$user]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message, ['{{ userName }}' => 'John Doe']);

        $this->userWithoutRoleValidator->validate($userCollection, $this->constraint);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected instance of "Doctrine\Common\Collections\Collection", "NULL" given
     */
    public function testNotACollection()
    {
        $this->userWithoutRoleValidator->validate(null, $this->constraint);
    }
}
