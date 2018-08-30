<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Validator\Constraints\UniqueUserEmail;
use Oro\Bundle\UserBundle\Validator\UniqueUserEmailValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueUserEmailValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserManager */
    private $userManager;

    /** @var UniqueUserEmail|\PHPUnit\Framework\MockObject\MockObject */
    private $constraint;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $executionContext;

    /** @var ConstraintViolationBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $constraintViolationBuilder;

    /** @var UniqueUserEmailValidator */
    private $validator;

    public function setUp()
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->executionContext = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new UniqueUserEmail();
        $this->validator = new UniqueUserEmailValidator($this->userManager);
        $this->validator->initialize($this->executionContext);
    }

    public function testValidateNewUserEmailIsUnique()
    {
        $newUserEmail = 'foo';
        /** @var User $newUser */
        $newUser = $this->getEntity(User::class, ['email' => $newUserEmail]);
        $this->userManager
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with($newUserEmail)
            ->willReturn(null);
        $this->executionContext
            ->expects(self::never())
            ->method('buildViolation');
        $this->validator->validate($newUser, $this->constraint);
    }

    public function testValidateUserEmailIsUnique()
    {
        $newUserEmail = 'foo';
        /** @var User $newUser */
        $newUser = $this->getEntity(User::class, ['email' => $newUserEmail, 'id' => 1]);
        $this->userManager
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with($newUserEmail)
            ->willReturn($newUser);
        $this->executionContext
            ->expects(self::never())
            ->method('buildViolation');
        $this->validator->validate($newUser, $this->constraint);
    }

    public function testValidateUserEmailIsNotUnique()
    {
        $newUserEmail = 'foo';
        /** @var User $newUser */
        $newUser = $this->getEntity(User::class, ['email' => $newUserEmail, 'id' => 1]);
        $existingUser = $this->getEntity(User::class, ['email' => $newUserEmail, 'id' => 2]);
        $this->userManager
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with($newUserEmail)
            ->willReturn($existingUser);
        $this->executionContext
            ->expects(self::once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($this->constraintViolationBuilder);
        $this->constraintViolationBuilder
            ->expects(self::at(0))
            ->method('atPath')
            ->with('email')
            ->willReturnSelf();
        $this->constraintViolationBuilder
            ->expects(self::at(1))
            ->method('setInvalidValue')
            ->with($newUserEmail)
            ->willReturnSelf();
        $this->constraintViolationBuilder
            ->expects(self::at(2))
            ->method('addViolation')
            ->willReturnSelf();
        $this->validator->validate($newUser, $this->constraint);
    }
}
