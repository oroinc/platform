<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\ImapBundle\Validator\Constraints\SmtpConnectionConfigurationValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SmtpConnectionConfigurationValidatorTest extends ConstraintValidatorTestCase
{
    /** @var SmtpSettingsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $checker;

    /** @var SmtpSettingsFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $smtpSettingsFactory;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(SmtpSettingsChecker::class);
        $this->smtpSettingsFactory = $this->createMock(SmtpSettingsFactory::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new SmtpConnectionConfigurationValidator(
            $this->checker,
            $this->smtpSettingsFactory
        );
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(UserEmailOrigin::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithUnsupportedType(): void
    {
        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateSmtpNotConfigured(): void
    {
        $value = $this->createUserEmailOrigin();
        $value->setSmtpHost('');

        $this->checker->expects(self::never())
            ->method('checkConnection');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFailedConnection(): void
    {
        $value = $this->createUserEmailOrigin();

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($this->createSmtpSettingsByUserEmailOrigin($value))
            ->willReturn(false);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateSuccessfulConnection(): void
    {
        $value = $this->createUserEmailOrigin();
        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($this->createSmtpSettingsByUserEmailOrigin($value))
            ->willReturn(true);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    private function createSmtpSettingsByUserEmailOrigin(UserEmailOrigin $value): SmtpSettings
    {
        $smtpSettings = new SmtpSettings(
            $value->getSmtpHost(),
            $value->getSmtpPort(),
            $value->getSmtpEncryption(),
            $value->getUser(),
            'decrypted_password'
        );
        $this->smtpSettingsFactory->expects(self::once())
            ->method('createFromUserEmailOrigin')
            ->with($value)
            ->willReturn($smtpSettings);

        return $smtpSettings;
    }

    private function createUserEmailOrigin(): UserEmailOrigin
    {
        $value = new UserEmailOrigin();
        $value->setSmtpHost('smtp.host');
        $value->setSmtpPort(123);
        $value->setSmtpEncryption('ssl');
        $value->setUser('user');
        $value->setPassword('encrypted_password');

        return $value;
    }
}
