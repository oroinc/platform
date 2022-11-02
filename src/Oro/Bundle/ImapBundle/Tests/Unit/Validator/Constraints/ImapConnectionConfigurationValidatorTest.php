<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Oro\Bundle\ImapBundle\Validator\Constraints\ImapConnectionConfiguration;
use Oro\Bundle\ImapBundle\Validator\Constraints\ImapConnectionConfigurationValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ImapConnectionConfigurationValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ImapSettingsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $checker;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(ImapSettingsChecker::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new ImapConnectionConfigurationValidator($this->checker);
    }

    private function createUserEmailOrigin(): UserEmailOrigin
    {
        $value = new UserEmailOrigin();
        $value->setImapHost('imap.host');
        $value->setImapPort(123);
        $value->setImapEncryption('ssl');
        $value->setUser('user');
        $value->setPassword('encrypted_password');

        return $value;
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(UserEmailOrigin::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithoutUserEmailOrigin()
    {
        $constraint = new ImapConnectionConfiguration();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateImapNotConfigured()
    {
        $value = $this->createUserEmailOrigin();
        $value->setImapHost('');
        $this->checker->expects($this->never())
            ->method('checkConnection');

        $constraint = new ImapConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFailedConnection()
    {
        $value = $this->createUserEmailOrigin();
        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($value)
            ->willReturn(false);

        $constraint = new ImapConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateSuccessfullyConnection()
    {
        $value = $this->createUserEmailOrigin();
        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($value)
            ->willReturn(true);

        $constraint = new ImapConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
