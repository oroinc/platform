<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class EmailAttachmentContentTest extends TestCase
{
    public function testIdGetter(): void
    {
        $entity = new EmailAttachmentContent();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testEmailAttachmentGetterAndSetter(): void
    {
        $emailAttachment = $this->createMock(EmailAttachment::class);

        $entity = new EmailAttachmentContent();
        $entity->setEmailAttachment($emailAttachment);

        $this->assertSame($emailAttachment, $entity->getEmailAttachment());
    }

    public function testValueGetterAndSetter(): void
    {
        $entity = new EmailAttachmentContent();
        $entity->setContent('test');
        $this->assertEquals('test', $entity->getContent());
    }

    public function testContentTransferEncodingGetterAndSetter(): void
    {
        $entity = new EmailAttachmentContent();
        $entity->setContentTransferEncoding('test');
        $this->assertEquals('test', $entity->getContentTransferEncoding());
    }
}
