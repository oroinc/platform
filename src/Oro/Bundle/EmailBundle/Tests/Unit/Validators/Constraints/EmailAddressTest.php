<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Validator\Constraints\EmailAddress;

class EmailAddressTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailAddress */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new EmailAddress();
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_email.email_address_validator', $this->constraint->validatedBy());
    }
}
