<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailRecipients;
use Oro\Bundle\EmailBundle\Validator\EmailRecipientsValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailRecipientsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new EmailRecipientsValidator();
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
