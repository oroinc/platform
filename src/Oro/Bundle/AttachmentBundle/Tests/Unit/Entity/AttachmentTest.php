<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class AttachmentTest extends \PHPUnit\Framework\TestCase
{
    /** @var Attachment */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new Attachment();
    }

    public function testComment()
    {
        $this->assertNull($this->entity->getComment());
        $comment = 'test comment';
        $this->entity->setComment($comment);
        $this->assertEquals($comment, $this->entity->getComment());
    }

    public function testCreatedAt()
    {
        $this->assertNull($this->entity->getCreatedAt());
        $date = new \DateTime('now');
        $this->entity->setCreatedAt($date);
        $this->assertEquals($date, $this->entity->getCreatedAt());
    }

    public function testUpdatedAt()
    {
        $this->assertNull($this->entity->getUpdatedAt());
        $date = new \DateTime('now');
        $this->entity->setUpdatedAt($date);
        $this->assertEquals($date, $this->entity->getUpdatedAt());
    }

    public function testFile()
    {
        $this->assertNull($this->entity->getFile());
        $file = new File();
        $this->entity->setFile($file);
        $this->assertSame($file, $this->entity->getFile());
    }

    public function testOwner()
    {
        $this->assertNull($this->entity->getOwner());
        $owner = new TestUser();
        $this->entity->setOwner($owner);
        $this->assertSame($owner, $this->entity->getOwner());
    }

    public function testOrganization()
    {
        $this->assertNull($this->entity->getOrganization());
        $organization = new Organization();
        $this->entity->setOrganization($organization);
        $this->assertSame($organization, $this->entity->getOrganization());
    }

    public function testPrePersists()
    {
        $testDate = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->entity->prePersist();
        $this->entity->preUpdate();

        $this->assertEquals($testDate->format('Y-m-d'), $this->entity->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals($testDate->format('Y-m-d'), $this->entity->getUpdatedAt()->format('Y-m-d'));
    }

    public function testGetOwnerId()
    {
        $this->assertNull($this->entity->getId());
        $testOwner = new TestUser();
        $this->entity->setOwner($testOwner);

        $this->assertEquals(1, $this->entity->getOwnerId());
    }

    public function testToString()
    {
        $this->assertEquals('', $this->entity->__toString());
        $file = new File();
        $file->setFilename('file.txt');
        $file->setOriginalFilename('original.txt');
        $this->entity->setFile($file);
        $this->assertEquals('file.txt (original.txt)', $this->entity->__toString());
    }
}
