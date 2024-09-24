<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfigurationValidator;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SmtpConnectionConfigurationValidatorTest extends ConstraintValidatorTestCase
{
    /** @var SmtpSettingsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $checker;

    /** @var SmtpSettingsFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $smtpSettingsFactory;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $encryptor;

    #[\Override]
    protected function setUp(): void
    {
        $this->checker = $this->createMock(SmtpSettingsChecker::class);
        $this->smtpSettingsFactory = $this->createMock(SmtpSettingsFactory::class);
        $this->encryptor = $this->createMock(SymmetricCrypterInterface::class);
        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): SmtpConnectionConfigurationValidator
    {
        return new SmtpConnectionConfigurationValidator(
            $this->checker,
            $this->smtpSettingsFactory,
            $this->encryptor
        );
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate([], $this->createMock(Constraint::class));
    }

    public function testValidateWithUnsupportedType(): void
    {
        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFailedConnection(): void
    {
        $encryptedPassword = 'encrypted_password';
        $value = $this->getConfiguredSettings($encryptedPassword);
        $smtpSettings = $this->getSmtpSettings($encryptedPassword);

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($smtpSettings)
            ->willReturn(false);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateSuccessfulConnection(): void
    {
        $encryptedPassword = 'encrypted_password';
        $value = $this->getConfiguredSettings($encryptedPassword);
        $smtpSettings = $this->getSmtpSettings($encryptedPassword);

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($smtpSettings)
            ->willReturn(true);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    private function getConfiguredSettings(string $encryptedPassword): array
    {
        return [
            'oro_email___smtp_settings_host' => [ConfigManager::VALUE_KEY => 'smtp.host'],
            'oro_email___smtp_settings_port' => [ConfigManager::VALUE_KEY => 123],
            'oro_email___smtp_settings_encryption' => [ConfigManager::VALUE_KEY => 'ssl'],
            'oro_email___smtp_settings_username' => [ConfigManager::VALUE_KEY => 'user'],
            'oro_email___smtp_settings_password' => [ConfigManager::VALUE_KEY => $encryptedPassword]
        ];
    }

    private function getSmtpSettings(string $encryptedPassword): SmtpSettings
    {
        $this->encryptor->expects(self::once())
            ->method('decryptData')
            ->with($encryptedPassword)
            ->willReturn('decrypted_password');

        $data = [
            'smtp.host',
            123,
            'ssl',
            'user',
            'decrypted_password'
        ];
        $smtpSettings = new SmtpSettings();
        $this->smtpSettingsFactory->expects(self::once())
            ->method('createFromArray')
            ->with($data)
            ->willReturn($smtpSettings);

        return $smtpSettings;
    }
}
