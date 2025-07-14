<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Entity;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class TaggingTest extends TestCase
{
    private Tagging $tagging;

    #[\Override]
    protected function setUp(): void
    {
        $this->tagging = new Tagging();
    }

    public function testSetGetUserMethods(): void
    {
        $user = $this->createMock(User::class);

        $this->tagging->setOwner($user);
        $this->assertEquals($user, $this->tagging->getOwner());
    }

    public function testSetGetTagMethods(): void
    {
        $tag = $this->createMock(Tag::class);
        $this->tagging->setTag($tag);

        $this->assertEquals($tag, $this->tagging->getTag());

        // test pass tag through constructor
        $tagging = new Tagging($tag);

        $this->assertEquals($tag, $tagging->getTag());
    }

    public function testSetGetResourceMethods(): void
    {
        $resource = $this->getMockForAbstractClass(Taggable::class);
        $resource->expects($this->exactly(2))
            ->method('getTaggableId')
            ->willReturn(1);

        $this->tagging->setResource($resource);

        $this->assertEquals(1, $this->tagging->getRecordId());
        $this->assertEquals(get_class($resource), $this->tagging->getEntityName());

        // test pass resource through constructor
        $tagging = new Tagging(null, $resource);

        $this->assertEquals(1, $tagging->getRecordId());
        $this->assertEquals(get_class($resource), $tagging->getEntityName());
    }

    public function testDateTimeMethods(): void
    {
        $timeCreated = new \DateTime('now');
        $this->tagging->setCreated($timeCreated);
        $this->assertEquals($timeCreated, $this->tagging->getCreated());
    }
}
