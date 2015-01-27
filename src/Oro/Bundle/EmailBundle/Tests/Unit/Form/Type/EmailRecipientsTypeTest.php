<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailRecipientsType;

class EmailRecipientsTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailRecipientsType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new EmailRecipientsType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_email_recipients', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_email_email_address', $this->formType->getParent());
    }
}
