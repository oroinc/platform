<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LoggerBundle\Validator\Constraints\EmailRecipientsList;
use Oro\Bundle\LoggerBundle\Validator\Constraints\EmailRecipientsListValidator;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class EmailRecipientsListValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailRecipientsListValidator */
    private $validator;

    /** @var EmailRecipientsList */
    private $constraint;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    protected function setUp(): void
    {
        $this->validator = new EmailRecipientsListValidator();
        $this->constraint = new EmailRecipientsList();
        $this->context = $this->createMock(ExecutionContextInterface::class);
    }

    public function testValidateException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            sprintf('Expected argument of type "%s", "%s" given', EmailRecipientsList::class, Email::class)
        );

        $this->validator->initialize($this->context);
        $this->validator->validate('', new Email());
    }

    public function testValidateWithEmptyValue(): void
    {
        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->initialize($this->context);
        $this->validator->validate('', $this->constraint);
    }

    public function testValidate(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->exactly(2))
            ->method('validate')
            ->withConsecutive(
                ['test1@@example.com', new Email()],
                ['test2@@example.com', new Email()]
            )
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList([new ConstraintViolation('', '', [], '', '', '')]),
                new ConstraintViolationList([new ConstraintViolation('', '', [], '', '', '')])
            );

        $this->context->expects($this->once())
            ->method('getValidator')
            ->willReturn($validator);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ value }}', 'test1@@example.com, test2@@example.com')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setPlural')
            ->with(2)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('oro.logger.system_configuration.fields.email_notification_recipients.error')
            ->willReturn($violationBuilder);

        $this->validator->initialize($this->context);
        $this->validator->validate('  test1@@example.com  ;  test2@@example.com  ', $this->constraint);
    }

    public function testValidateNoError(): void
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('test@example.com', new Email())
            ->willReturn(new ConstraintViolationList());

        $this->context->expects($this->once())
            ->method('getValidator')
            ->willReturn($validator);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->initialize($this->context);
        $this->validator->validate('  test@example.com  ', $this->constraint);
    }
}
