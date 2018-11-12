<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailRecipients;
use Oro\Bundle\EmailBundle\Validator\EmailRecipientsValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailRecipientsValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailRecipients */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    protected function setUp()
    {
        $this->constraint = new EmailRecipients();
        $this->context = $this->createMock(ExecutionContextInterface::class);
    }

    public function testValidateNoErrors()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $email = new Email();
        $email->setTo(['test1@mail.com'])
            ->setCc(['test2@mail.com'])
            ->setBcc(['test3@mail.com']);
        $this->getValidator()->validate($email, $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message);
        $email = new Email();
        $this->getValidator()->validate($email, $this->constraint);
    }

    /**
     * @return EmailRecipientsValidator
     */
    protected function getValidator()
    {
        $validator = new EmailRecipientsValidator();
        $validator->initialize($this->context);
        return $validator;
    }
}
