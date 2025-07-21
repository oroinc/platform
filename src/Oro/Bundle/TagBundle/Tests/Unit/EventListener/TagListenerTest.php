<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\EventListener\TagListener;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TagListenerTest extends TestCase
{
    private TaggableHelper&MockObject $taggableHelper;
    private TagManager&MockObject $tagManager;
    private TagListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->taggableHelper = $this->createMock(TaggableHelper::class);
        $this->tagManager = $this->createMock(TagManager::class);

        $container = TestContainerBuilder::create()
            ->add('oro_tag.helper.taggable_helper', $this->taggableHelper)
            ->add('oro_tag.tag.manager', $this->tagManager)
            ->getContainer($this);

        $this->listener = new TagListener($container);
    }

    public function testPreRemoveForTaggableEntity(): void
    {
        $entity = new \stdClass();

        $this->taggableHelper->expects(self::once())
            ->method('isTaggable')
            ->with(self::identicalTo($entity))
            ->willReturn(true);
        $this->tagManager->expects(self::once())
            ->method('deleteTagging')
            ->with(self::identicalTo($entity), []);

        $this->listener->preRemove(
            new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class))
        );
    }

    public function testPreRemoveForNotTaggableEntity(): void
    {
        $entity = new \stdClass();

        $this->taggableHelper->expects(self::once())
            ->method('isTaggable')
            ->with(self::identicalTo($entity))
            ->willReturn(false);
        $this->tagManager->expects(self::never())
            ->method('deleteTagging');

        $this->listener->preRemove(
            new LifecycleEventArgs($entity, $this->createMock(EntityManagerInterface::class))
        );
    }
}
