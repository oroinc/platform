<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    private Tag $tag;

    #[\Override]
    protected function setUp(): void
    {
        $this->tag = new Tag();

        $this->assertEquals(null, $this->tag->getId());
    }

    public function testSetGetNameMethods(): void
    {
        $this->tag->setName('test');
        $this->assertEquals('test', $this->tag->getName());

        $tag = new Tag('test 2');
        $this->assertEquals('test 2', $tag->getName());
        $this->assertEquals('test 2', $tag->__toString());
    }

    public function testDateTimeMethods(): void
    {
        $timeCreated = new \DateTime('now');
        $timeUpdated = new \DateTime('now');

        $this->tag->setCreated($timeCreated);
        $this->tag->setUpdated($timeUpdated);

        $this->assertEquals($timeCreated, $this->tag->getCreated());
        $this->assertEquals($timeUpdated, $this->tag->getUpdated());
    }

    public function testAuthorAndUpdaterStoring(): void
    {
        $user = $this->createMock(User::class);

        $this->tag->setOwner($user);
        $this->assertEquals($user, $this->tag->getOwner());
    }

    public function testUpdatedTime(): void
    {
        $this->tag->doUpdate();
        $oldUpdatedTime = $this->tag->getUpdated();
        sleep(1);
        $this->tag->doUpdate();
        $this->assertInstanceOf(\DateTime::class, $this->tag->getUpdated());
        $this->assertNotEquals($oldUpdatedTime, $this->tag->getUpdated());
    }

    public function testGetTagging(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->tag->getTagging());
    }

    public function testOwners(): void
    {
        $entity = $this->tag;
        $user = new User();

        $this->assertEmpty($entity->getOwner());

        $entity->setOwner($user);

        $this->assertEquals($user, $entity->getOwner());
    }

    public function testOrganization(): void
    {
        $entity = $this->tag;
        $organization = new Organization();

        $this->assertNull($entity->getOrganization());
        $entity->setOrganization($organization);
        $this->assertSame($organization, $entity->getOrganization());
    }
}
