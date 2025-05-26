<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    private Attachment $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new Attachment();
    }

    public function testComment(): void
    {
        $this->assertNull($this->entity->getComment());
        $comment = 'test comment';
        $this->entity->setComment($comment);
        $this->assertEquals($comment, $this->entity->getComment());
    }

    public function testCreatedAt(): void
    {
        $this->assertNull($this->entity->getCreatedAt());
        $date = new \DateTime('now');
        $this->entity->setCreatedAt($date);
        $this->assertEquals($date, $this->entity->getCreatedAt());
    }

    public function testUpdatedAt(): void
    {
        $this->assertNull($this->entity->getUpdatedAt());
        $date = new \DateTime('now');
        $this->entity->setUpdatedAt($date);
        $this->assertEquals($date, $this->entity->getUpdatedAt());
    }

    public function testFile(): void
    {
        $this->assertNull($this->entity->getFile());
        $file = new File();
        $this->entity->setFile($file);
        $this->assertSame($file, $this->entity->getFile());
    }

    public function testOwner(): void
    {
        $this->assertNull($this->entity->getOwner());
        $owner = new TestUser();
        $this->entity->setOwner($owner);
        $this->assertSame($owner, $this->entity->getOwner());
    }

    public function testOrganization(): void
    {
        $this->assertNull($this->entity->getOrganization());
        $organization = new Organization();
        $this->entity->setOrganization($organization);
        $this->assertSame($organization, $this->entity->getOrganization());
    }

    public function testPrePersists(): void
    {
        $testDate = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->entity->prePersist();
        $this->entity->preUpdate();

        $this->assertEquals($testDate->format('Y-m-d'), $this->entity->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals($testDate->format('Y-m-d'), $this->entity->getUpdatedAt()->format('Y-m-d'));
    }

    public function testGetOwnerId(): void
    {
        $this->assertNull($this->entity->getId());
        $testOwner = new TestUser();
        $this->entity->setOwner($testOwner);

        $this->assertEquals(1, $this->entity->getOwnerId());
    }

    public function testToString(): void
    {
        $this->assertEquals('', $this->entity->__toString());

        $file = new File();
        $file->setFilename('file.txt');
        $this->entity->setFile($file);
        $this->assertEquals('file.txt', $this->entity->__toString());

        $file->setOriginalFilename('original.txt');
        $this->assertEquals('file.txt (original.txt)', $this->entity->__toString());
    }
}
