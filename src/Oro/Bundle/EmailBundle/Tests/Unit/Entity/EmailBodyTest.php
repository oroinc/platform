<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class EmailBodyTest extends \PHPUnit\Framework\TestCase
{
    public function testIdGetter()
    {
        $entity = new EmailBody();
        ReflectionUtil::setId($entity, 1);
        $this->assertEquals(1, $entity->getId());
    }

    public function testContentGetterAndSetter()
    {
        $entity = new EmailBody();
        $entity->setBodyContent('test');
        $this->assertEquals('test', $entity->getBodyContent());
    }

    public function testBodyIsTextGetterAndSetter()
    {
        $entity = new EmailBody();
        $entity->setBodyIsText(true);
        $this->assertEquals(true, $entity->getBodyIsText());
    }

    public function testHasAttachmentsGetterAndSetter()
    {
        $entity = new EmailBody();
        $entity->setHasAttachments(true);
        $this->assertEquals(true, $entity->getHasAttachments());
    }

    public function testPersistentGetterAndSetter()
    {
        $entity = new EmailBody();
        $entity->setPersistent(true);
        $this->assertEquals(true, $entity->getPersistent());
    }

    public function testAttachmentGetterAndSetter()
    {
        $attachment = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailAttachment');

        $entity = new EmailBody();
        $entity->addAttachment($attachment);

        $attachments = $entity->getAttachments();

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attachments);
        $this->assertCount(1, $attachments);
        $this->assertTrue($attachment === $attachments[0]);
    }

    public function testBeforeSave()
    {
        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $entity = new EmailBody();
        $entity->beforeSave();

        $this->assertEquals(false, $entity->getBodyIsText());
        $this->assertEquals(false, $entity->getHasAttachments());
        $this->assertEquals(false, $entity->getPersistent());
        $this->assertGreaterThanOrEqual($createdAt, $entity->getCreated());
    }

    public function testTextBodyGetterAndSetter()
    {
        $entity = new EmailBody();
        self::assertNull($entity->getTextBody());
        $entity->setTextBody('some text');
        self::assertEquals('some text', $entity->getTextBody());
    }
}
