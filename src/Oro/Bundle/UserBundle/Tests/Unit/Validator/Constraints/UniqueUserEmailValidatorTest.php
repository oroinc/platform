<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Validator\Constraints\UniqueUserEmail;
use Oro\Bundle\UserBundle\Validator\Constraints\UniqueUserEmailValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueUserEmailValidatorTest extends ConstraintValidatorTestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new UniqueUserEmailValidator($this->userManager);
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(User::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotUser()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new UniqueUserEmail());
    }

    public function testValidateNewUserWhenEmailIsNull()
    {
        $newUser = new User();
        $this->userManager->expects(self::never())
            ->method('findUserByEmail');

        $this->validator->validate($newUser, new UniqueUserEmail());

        $this->assertNoViolation();
    }

    public function testValidateNewUserEmailIsUnique()
    {
        $newUserEmail = 'foo';
        $newUser = new User();
        $newUser->setEmail($newUserEmail);
        $this->userManager->expects(self::once())
            ->method('findUserByEmail')
            ->with($newUserEmail)
            ->willReturn(null);

        $this->validator->validate($newUser, new UniqueUserEmail());

        $this->assertNoViolation();
    }

    public function testValidateUserEmailIsUnique()
    {
        $newUserEmail = 'foo';
        $newUser = new User();
        ReflectionUtil::setId($newUser, 1);
        $newUser->setEmail($newUserEmail);
        $this->userManager->expects(self::once())
            ->method('findUserByEmail')
            ->with($newUserEmail)
            ->willReturn($newUser);

        $this->validator->validate($newUser, new UniqueUserEmail());

        $this->assertNoViolation();
    }

    public function testValidateUserEmailIsNotUnique()
    {
        $newUserEmail = 'foo';
        $newUser = new User();
        ReflectionUtil::setId($newUser, 1);
        $newUser->setEmail($newUserEmail);
        $existingUser = new User();
        ReflectionUtil::setId($existingUser, 2);
        $existingUser->setEmail($newUserEmail);
        $this->userManager->expects(self::once())
            ->method('findUserByEmail')
            ->with($newUserEmail)
            ->willReturn($existingUser);

        $constraint = new UniqueUserEmail();
        $this->validator->validate($newUser, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.email')
            ->setInvalidValue($newUserEmail)
            ->assertRaised();
    }
}
