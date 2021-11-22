<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailRecipients;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailRecipientsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailRecipientsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new EmailRecipientsValidator();
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Email::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotEmail()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new EmailRecipients());
    }

    public function testValidateNoErrors()
    {
        $email = new Email();
        $email->setTo(['test1@mail.com'])
            ->setCc(['test2@mail.com'])
            ->setBcc(['test3@mail.com']);

        $constraint = new EmailRecipients();
        $this->validator->validate($email, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithErrors()
    {
        $email = new Email();

        $constraint = new EmailRecipients();
        $this->validator->validate($email, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
