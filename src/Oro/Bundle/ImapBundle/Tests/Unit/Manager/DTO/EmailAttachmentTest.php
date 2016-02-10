<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Oro\Bundle\ImapBundle\Manager\DTO\EmailAttachment;

class EmailAttachmentTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetters()
    {
        $obj = new EmailAttachment();
        $obj
            ->setFileName('testFileName')
            ->setContentType('testContentType')
            ->setContentTransferEncoding('testContentTransferEncoding')
            ->setContent('testContent')
            ->setFileSize(10);
        $this->assertEquals('testFileName', $obj->getFileName());
        $this->assertEquals('testContentType', $obj->getContentType());
        $this->assertEquals('testContentTransferEncoding', $obj->getContentTransferEncoding());
        $this->assertEquals('testContent', $obj->getContent());
        $this->assertEquals('10', $obj->getFileSize());
    }
}
