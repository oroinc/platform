<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettingsFactory;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Validator\Constraints\SmtpConnectionConfiguration;
use Oro\Bundle\EmailBundle\Validator\SmtpConnectionConfigurationValidator;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SmtpConnectionConfigurationValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var SmtpConnectionConfiguration */
    private $constraint;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var SmtpSettingsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $checker;

    /** @var SmtpSettingsFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $smtpSettingsFactory;

    /** @var SmtpConnectionConfigurationValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->constraint = new SmtpConnectionConfiguration();

        $this->checker = $this->createMock(SmtpSettingsChecker::class);
        $this->smtpSettingsFactory = $this->createMock(SmtpSettingsFactory::class);

        $this->validator = new SmtpConnectionConfigurationValidator($this->checker, $this->smtpSettingsFactory);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithUnsupportedType()
    {
        $this->assertViolationNotAdded();

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateSmtpNotConfigured()
    {
        $this->assertViolationNotAdded();

        $value = $this->createUserEmailOrigin();
        $value->setSmtpHost('');
        $this->checker->expects($this->never())
            ->method('checkConnection');

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateFailedConnection()
    {
        $this->assertViolationAdded();

        $value = $this->createUserEmailOrigin();
        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($this->createSmtpSettingsByUserEmailOrigin($value))
            ->willReturn('Test error message');

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateSuccessfullConnection()
    {
        $this->assertViolationNotAdded();

        $value = $this->createUserEmailOrigin();
        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($this->createSmtpSettingsByUserEmailOrigin($value))
            ->willReturn('');

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateArray()
    {
        $this->assertViolationAdded();

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

        $this->validator->validate($value, $this->constraint);
    }

    private function assertViolationAdded()
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('addViolation');
    }

    private function assertViolationNotAdded()
    {
        $this->context->expects($this->never())
            ->method('buildViolation')
            ->with($this->constraint->message);
    }

    /**
     * @param UserEmailOrigin $value
     *
     * @return SmtpSettings
     */
    private function createSmtpSettingsByUserEmailOrigin(UserEmailOrigin $value)
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

    /**
     * @return UserEmailOrigin
     */
    private function createUserEmailOrigin()
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
