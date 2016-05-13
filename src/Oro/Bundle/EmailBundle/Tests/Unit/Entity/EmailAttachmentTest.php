<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class EmailAttachmentTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $entity = new EmailAttachment();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testFileNameGetterAndSetter()
    {
        $entity = new EmailAttachment();
        $entity->setFileName('test');
        $this->assertEquals('test', $entity->getFileName());
    }

    public function testContentTypeGetterAndSetter()
    {
        $entity = new EmailAttachment();
        $entity->setContentType('test');
        $this->assertEquals('test', $entity->getContentType());
    }

    public function testContentGetterAndSetter()
    {
        $content = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent');

        $entity = new EmailAttachment();
        $entity->setContent($content);

        $this->assertTrue($content === $entity->getContent());
    }

    public function testEmailBodyGetterAndSetter()
    {
        $emailBody = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailBody');

        $entity = new EmailAttachment();
        $entity->setEmailBody($emailBody);

        $this->assertTrue($emailBody === $entity->getEmailBody());
    }
    
    public function testGetSize()
    {
        $file = $this->getMock('Oro\Bundle\AttachmentBundle\Entity\File');
        $file->expects($this->once())
            ->method('getFileSize')
            ->will($this->returnValue(100));
        $entity = new EmailAttachment();
        $entity->setFile($file);
        $this->assertTrue($entity->getSize() === 100);

        $entity = new EmailAttachment();
        $attachmentContent = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent');
        $attachmentContent->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(base64_encode('1234')));
        $attachmentContent->expects($this->once())
            ->method('getContentTransferEncoding')
            ->will($this->returnValue('base64'));
        $entity->setContent($attachmentContent);
        $this->assertTrue($entity->getSize() === 4);
    }
}
