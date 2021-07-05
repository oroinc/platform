<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\EmailBundle\Validator\SmtpConnectionConfigurationValidator;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
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
        return new SmtpConnectionConfigurationValidator($this->checker, $this->smtpSettingsFactory);
    }

    public function testValidateWithUnsupportedType()
    {
        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateSmtpNotConfigured()
    {
        $value = $this->createUserEmailOrigin();
        $value->setSmtpHost('');

        $this->checker->expects($this->never())
            ->method('checkConnection');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFailedConnection()
    {
        $value = $this->createUserEmailOrigin();

        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($this->createSmtpSettingsByUserEmailOrigin($value))
            ->willReturn('Test error message');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateSuccessfullConnection()
    {
        $value = $this->createUserEmailOrigin();
        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($this->createSmtpSettingsByUserEmailOrigin($value))
            ->willReturn('');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateArray()
    {
        $value = ['key' => 'value'];
        $smtpSettings = new SmtpSettings();

        $this->smtpSettingsFactory->expects($this->once())
            ->method('create')
            ->with($value)
            ->willReturn($smtpSettings);

        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($smtpSettings)
            ->willReturn('Test error message');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
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
        $this->smtpSettingsFactory->expects($this->once())
            ->method('create')
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
