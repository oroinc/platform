<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Validator\Constraints\UserAuthenticationFields;
use Oro\Bundle\UserBundle\Validator\Constraints\UserAuthenticationFieldsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UserAuthenticationFieldsValidatorTest extends ConstraintValidatorTestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        parent::setUp();
    }

    protected function createValidator(): UserAuthenticationFieldsValidator
    {
        return new UserAuthenticationFieldsValidator($this->userManager);
    }

    private function getUser(int $id = null): User
    {
        $user = new User();
        if (null !== $id) {
            $user->setId($id);
        }

        return $user;
    }

    public function testGetTargets()
    {
        $constraint = new UserAuthenticationFields();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(User::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotUser()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new UserAuthenticationFields());
    }

    /**
     * User username = User email, Username not in email format
     */
    public function testUsernameValid()
    {
        $user = $this->getUser(1);
        $user->setUsername('username');
        $user->setEmail('username@example.com');

        $constraint = new UserAuthenticationFields();
        $this->validator->validate($user, $constraint);

        $this->assertNoViolation();
    }

    /**
     * User username = User email, Username in email format
     */
    public function testUsernameValidUsernameAsEmail()
    {
        $user = $this->getUser(1);
        $user->setUsername('username@example.com');
        $user->setEmail('username@example.com');

        $constraint = new UserAuthenticationFields();
        $this->validator->validate($user, $constraint);

        $this->assertNoViolation();
    }

    /**
     * User with email as current user Username not exist, Username in email format
     */
    public function testUsernameValidUsernameInEmailFormat()
    {
        $user = $this->getUser(1);
        $user->setUsername('username@example.com');
        $user->setEmail('test@example.com');

        $existingUser = null;

        $this->userManager->expects($this->once())
            ->method('findUserByEmail')
            ->with('username@example.com')
            ->willReturn($existingUser);

        $constraint = new UserAuthenticationFields();
        $this->validator->validate($user, $constraint);

        $this->assertNoViolation();
    }

    /**
     * User username = existing user email, Username in email format
     */
    public function testUsernameNotValidUsernameInEmailFormat()
    {
        $user = $this->getUser(1);
        $user->setUsername('username@example.com');
        $user->setEmail('test@example.com');

        $existingUser = $this->getUser(2);
        $existingUser->setUsername('username');
        $existingUser->setEmail('username@example.com');

        $this->userManager->expects($this->once())
            ->method('findUserByEmail')
            ->with('username@example.com')
            ->willReturn($existingUser);

        $constraint = new UserAuthenticationFields();
        $this->validator->validate($user, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.username')
            ->assertRaised();
    }

    public function testUsernameIsNull()
    {
        $user = $this->getUser(1);
        $user->setEmail('test@example.com');

        $this->userManager->expects($this->never())
            ->method('findUserByEmail');

        $constraint = new UserAuthenticationFields();
        $this->validator->validate($user, $constraint);

        $this->assertNoViolation();
    }
}
