<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestUser;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class AttachmentTest extends EntityTestAbstract
{
    protected function setUp()
    {
        $this->entity = new Attachment();
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $comment = 'test comment';
        $file = new File();
        $date = new \DateTime('now');
        $owner = new TestUser();
        $organization = new Organization();

        return [
            'comment' => ['comment', $comment, $comment],
            'file' => ['file', $file, $file],
            'createdAt' => ['createdAt', $date, $date],
            'updatedAt' => ['updatedAt', $date, $date],
            'owner' => ['owner', $owner, $owner],
            'organization' => ['organization', $organization, $organization]
        ];
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
