<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailAddresserType;

class EmailAddresserTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailAddresserType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new EmailAddresserType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_email_addresser', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_email_email_address', $this->formType->getParent());
    }
}
