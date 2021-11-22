<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailAddress;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailAddressValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class EmailAddressValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailAddress */
    private $constraint;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var EmailAddressHelper */
    private $emailAddressHelper;

    protected function setUp(): void
    {
        $this->constraint = new EmailAddress();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->emailAddressHelper = new EmailAddressHelper();
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->getValidator()->validate('test@example.com', $this->createMock(Constraint::class));
    }

    public function testValidateNoErrors()
    {
        $violationList = new ConstraintViolationList();
        $this->context->expects($this->once())
            ->method('getViolations')
            ->willReturn($violationList);
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->getValidator()->validate('testname <test@mail.com>', $this->constraint);
    }

    public function testValidateEmptyValue()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->getValidator()->validate('', $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $violationList = new ConstraintViolationList();
        $violation1 = $this->createMock(ConstraintViolation::class);
        $violation1->expects($this->any())
            ->method('getPropertyPath')
            ->willReturn('from');
        $violationList->add($violation1);
        $violation2 = $this->createMock(ConstraintViolation::class);
        $violation2->expects($this->any())
            ->method('getPropertyPath')
            ->willReturn('to');
        $violationList->add($violation2);
        $violation3 = $this->createMock(ConstraintViolation::class);
        $violation3->expects($this->any())
            ->method('getPropertyPath')
            ->willReturn('cc');
        $violationList->add($violation3);

        $this->context->expects($this->any())
            ->method('getViolations')
            ->willReturn($violationList);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->any())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder);
        $builder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $builder->expects($this->any())
            ->method('setCode')
            ->willReturnSelf();
        $builder->expects($this->any())
            ->method('addViolation');

        $this->context->expects($this->any())
            ->method('getPropertyPath')
            ->willReturn('cc');
        $this->getValidator()->validate(['test wrong email 1', 'test wrong email 2'], $this->constraint);
    }

    private function getValidator(): EmailAddressValidator
    {
        $validator = new EmailAddressValidator($this->emailAddressHelper);
        $validator->initialize($this->context);

        return $validator;
    }
}
