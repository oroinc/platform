<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Model\ExtendAttachment;

class ExtendAttachmentTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtendAttachment */
    protected $extendedAttachment;

    public function setUp()
    {
        $this->extendedAttachment = new ExtendAttachment();
    }

    public function testTarget()
    {
        $this->assertNull($this->extendedAttachment->getTarget());
        $this->assertEquals($this->extendedAttachment, $this->extendedAttachment->setTarget(new \stdClass()));
    }
}
