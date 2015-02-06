<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EmailBundle\Validator\Constraints\EmailRecipients;

class EmailRecipientsTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailRecipients */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new EmailRecipients();
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_email.email_recipients_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
