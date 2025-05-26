<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class EmailBodyTest extends TestCase
{
    public function testIdGetter(): void
    {
        $entity = new EmailBody();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testContentGetterAndSetter(): void
    {
        $entity = new EmailBody();
        $entity->setBodyContent('test');
        $this->assertEquals('test', $entity->getBodyContent());
    }

    public function testBodyIsTextGetterAndSetter(): void
    {
        $entity = new EmailBody();
        $entity->setBodyIsText(true);
        $this->assertEquals(true, $entity->getBodyIsText());
    }

    public function testHasAttachmentsGetterAndSetter(): void
    {
        $entity = new EmailBody();
        $entity->setHasAttachments(true);
        $this->assertEquals(true, $entity->getHasAttachments());
    }

    public function testPersistentGetterAndSetter(): void
    {
        $entity = new EmailBody();
        $entity->setPersistent(true);
        $this->assertEquals(true, $entity->getPersistent());
    }

    public function testAttachmentGetterAndSetter(): void
    {
        $attachment = $this->createMock(EmailAttachment::class);

        $entity = new EmailBody();
        $entity->addAttachment($attachment);

        $attachments = $entity->getAttachments();

        $this->assertInstanceOf(ArrayCollection::class, $attachments);
        $this->assertCount(1, $attachments);
        $this->assertSame($attachment, $attachments[0]);
    }

    public function testBeforeSave(): void
    {
        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $entity = new EmailBody();
        $entity->beforeSave();

        $this->assertEquals(false, $entity->getBodyIsText());
        $this->assertEquals(false, $entity->getHasAttachments());
        $this->assertEquals(false, $entity->getPersistent());
        $this->assertGreaterThanOrEqual($createdAt, $entity->getCreated());
    }

    public function testTextBodyGetterAndSetter(): void
    {
        $entity = new EmailBody();
        self::assertNull($entity->getTextBody());
        $entity->setTextBody('some text');
        self::assertEquals('some text', $entity->getTextBody());
    }
}
