<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Validator\Constraints\EmailRecipients;
use Symfony\Component\Validator\Constraint;

class EmailRecipientsTest extends \PHPUnit\Framework\TestCase
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
