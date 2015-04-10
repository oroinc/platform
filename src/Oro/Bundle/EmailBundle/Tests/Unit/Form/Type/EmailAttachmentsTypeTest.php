<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentsType;

class EmailAttachmentsTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailAttachmentsType
     */
    protected $emailAttachmentsType;

    protected function setUp()
    {
        $this->emailAttachmentsType = new EmailAttachmentsType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_attachments', $this->emailAttachmentsType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('collection', $this->emailAttachmentsType->getParent());
    }
}
