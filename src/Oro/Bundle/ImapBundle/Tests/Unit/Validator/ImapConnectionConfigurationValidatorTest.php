<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Validator;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\ImapSettingsChecker;
use Oro\Bundle\ImapBundle\Validator\Constraints\ImapConnectionConfiguration;
use Oro\Bundle\ImapBundle\Validator\ImapConnectionConfigurationValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ImapConnectionConfigurationValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImapConnectionConfiguration */
    private $constraint;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var ImapSettingsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $checker;

    /** @var ImapConnectionConfigurationValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->constraint = new ImapConnectionConfiguration();

        $this->checker = $this->createMock(ImapSettingsChecker::class);
        $this->validator = new ImapConnectionConfigurationValidator($this->checker);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithoutUserEmailOrigin()
    {
        $this->assertViolationNotAdded();

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateImapNotConfigured()
    {
        $this->assertViolationNotAdded();

        $value = $this->createUserEmailOrigin();
        $value->setImapHost('');
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
            ->with($value)
            ->willReturn(false);

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateSuccessfullConnection()
    {
        $this->assertViolationNotAdded();

        $value = $this->createUserEmailOrigin();
        $this->checker->expects($this->once())
            ->method('checkConnection')
            ->with($value)
            ->willReturn(true);

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
            ->method('buildViolation');
    }

    /**
     * @return UserEmailOrigin
     */
    private function createUserEmailOrigin()
    {
        $value = new UserEmailOrigin();
        $value->setImapHost('imap.host');
        $value->setImapPort(123);
        $value->setImapEncryption('ssl');
        $value->setUser('user');
        $value->setPassword('encrypted_password');

        return $value;
    }
}
