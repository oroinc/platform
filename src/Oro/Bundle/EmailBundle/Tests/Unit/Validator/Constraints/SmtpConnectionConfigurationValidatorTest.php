<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfigurationValidator;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SmtpConnectionConfigurationValidatorTest extends ConstraintValidatorTestCase
{
    private const HOST = 'smtp.host';
    private const PORT = 123;
    private const ENCRYPTION = 'ssl';
    private const USERNAME = 'user';
    private const ENCRYPTED_PASSWORD = 'encrypted_password';
    private const DECRYPTED_PASSWORD = 'decrypted_password';

    private SmtpSettingsChecker&MockObject $checker;
    private SmtpSettingsFactory&MockObject $smtpSettingsFactory;
    private SymmetricCrypterInterface&MockObject $encryptor;

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

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate([], $this->createMock(Constraint::class));
    }

    public function testValidateWithUnsupportedType(): void
    {
        $this->checker->expects(self::never())
            ->method('checkConnection');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNoDataToValidateConnection(): void
    {
        $value = [];

        $this->checker->expects(self::never())
            ->method('checkConnection');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider noDataToCheckConnectionDataProvider
     */
    public function testValidateWhenNoDataToCheckConnection(array $value): void
    {
        $this->checker->expects(self::never())
            ->method('checkConnection');
        $this->smtpSettingsFactory->expects(self::never())
            ->method('createFromArray');

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function noDataToCheckConnectionDataProvider(): array
    {
        return [
            'empty host' => [
                $this->getConfiguredSettings(host: ''),
            ],
            'empty host, no credentials' => [
                $this->getConfiguredSettings(host: '', username: '', password: ''),
            ],
            'null port' => [
                $this->getConfiguredSettings(port: null),
            ],
            'null port, no credentials' => [
                $this->getConfiguredSettings(port: null, username: '', password: ''),
            ],
            'zero port' => [
                $this->getConfiguredSettings(port: 0),
            ],
            'zero port, no credentials' => [
                $this->getConfiguredSettings(port: 0, username: '', password: ''),
            ],
            'non-numeric port' => [
                $this->getConfiguredSettings(port: 'abc'),
            ],
            'non-numeric port, no credentials' => [
                $this->getConfiguredSettings(port: 'abc', username: '', password: ''),
            ],
            'only username' => [
                $this->getConfiguredSettings(host: '', port: null, encryption: '', password: ''),
            ],
        ];
    }

    public function testValidateFailedConnection(): void
    {
        $value = $this->getConfiguredSettings();

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($this->expectSmtpSettings())
            ->willReturn(false);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateSuccessfulConnection(): void
    {
        $value = $this->getConfiguredSettings();

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($this->expectSmtpSettings())
            ->willReturn(true);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFailedConnectionWithoutCredentials(): void
    {
        $value = $this->getConfiguredSettings(username: '', password: '');

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($this->expectSmtpSettings(username: '', encryptedPassword: '', decryptedPassword: ''))
            ->willReturn(false);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateSuccessfulConnectionWithoutCredentials(): void
    {
        $value = $this->getConfiguredSettings(username: '', password: '');

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($this->expectSmtpSettings(username: '', encryptedPassword: '', decryptedPassword: ''))
            ->willReturn(true);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateSuccessfulConnectionWithoutCredentialsAndEncryption(): void
    {
        $value = $this->getConfiguredSettings(encryption: '', username: '', password: '');

        $this->checker->expects(self::once())
            ->method('checkConnection')
            ->with($this->expectSmtpSettings(
                encryption: '',
                username: '',
                encryptedPassword: '',
                decryptedPassword: ''
            ))
            ->willReturn(true);

        $constraint = new SmtpConnectionConfiguration();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    private function getConfiguredSettings(
        ?string $host = self::HOST,
        mixed $port = self::PORT,
        ?string $encryption = self::ENCRYPTION,
        ?string $username = self::USERNAME,
        ?string $password = self::ENCRYPTED_PASSWORD
    ): array {
        return [
            'oro_email___smtp_settings_host' => [ConfigManager::VALUE_KEY => $host],
            'oro_email___smtp_settings_port' => [ConfigManager::VALUE_KEY => $port],
            'oro_email___smtp_settings_encryption' => [ConfigManager::VALUE_KEY => $encryption],
            'oro_email___smtp_settings_username' => [ConfigManager::VALUE_KEY => $username],
            'oro_email___smtp_settings_password' => [ConfigManager::VALUE_KEY => $password]
        ];
    }

    /**
     * Expects the settings model to be built from the given parameters and returns it.
     */
    private function expectSmtpSettings(
        string $host = self::HOST,
        int $port = self::PORT,
        ?string $encryption = self::ENCRYPTION,
        ?string $username = self::USERNAME,
        ?string $encryptedPassword = self::ENCRYPTED_PASSWORD,
        ?string $decryptedPassword = self::DECRYPTED_PASSWORD
    ): SmtpSettings {
        $this->encryptor->expects(self::once())
            ->method('decryptData')
            ->with($encryptedPassword)
            ->willReturn($decryptedPassword);

        $smtpSettings = new SmtpSettings();
        $this->smtpSettingsFactory->expects(self::once())
            ->method('createFromArray')
            ->with([$host, $port, $encryption, $username, $decryptedPassword])
            ->willReturn($smtpSettings);

        return $smtpSettings;
    }
}
