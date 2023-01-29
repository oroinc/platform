<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\EventListener\TagListener;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class TagListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaggableHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $taggableHelper;

    /** @var TagManager|\PHPUnit\Framework\MockObject\MockObject */
    private $tagManager;

    /** @var TagListener */
    private $listener;

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

    public function testPreRemoveForTaggableEntity()
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

    public function testPreRemoveForNotTaggableEntity()
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
