<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Component\Testing\ReflectionUtil;

class EmailAttachmentTest extends \PHPUnit\Framework\TestCase
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
        $content = $this->createMock(EmailAttachmentContent::class);

        $entity = new EmailAttachment();
        $entity->setContent($content);

        $this->assertSame($content, $entity->getContent());
    }

    public function testEmailBodyGetterAndSetter()
    {
        $emailBody = $this->createMock(EmailBody::class);

        $entity = new EmailAttachment();
        $entity->setEmailBody($emailBody);

        $this->assertSame($emailBody, $entity->getEmailBody());
    }

    public function testGetSize()
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())
            ->method('getFileSize')
            ->willReturn(100);
        $entity = new EmailAttachment();
        $entity->setFile($file);
        $this->assertSame(100, $entity->getSize());

        $entity = new EmailAttachment();
        $attachmentContent = $this->createMock(EmailAttachmentContent::class);
        $attachmentContent->expects($this->once())
            ->method('getContent')
            ->willReturn(base64_encode('1234'));
        $attachmentContent->expects($this->once())
            ->method('getContentTransferEncoding')
            ->willReturn('base64');
        $entity->setContent($attachmentContent);
        $this->assertSame(4, $entity->getSize());
    }
}
