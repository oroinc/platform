<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Form\EventSubscriber\TagSubscriber;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Entity;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class TagSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var TaggableHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $taggableHelper;

    /** @var TagSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(TagManager::class);
        $this->taggableHelper = $this->createMock(TaggableHelper::class);

        $this->subscriber = new TagSubscriber($this->manager, $this->taggableHelper);
    }

    public function testSubscribedEvents()
    {
        $result = TagSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);

        $this->assertEquals('preSet', $result[FormEvents::PRE_SET_DATA]);
    }

    /**
     * @dataProvider entityProvider
     */
    public function testPreSet(object $entity, bool $shouldSetData)
    {
        $eventMock = $this->createMock(FormEvent::class);

        $parentFormMock = $this->createMock(FormInterface::class);
        $parentFormMock->expects($this->once())
            ->method('getData')
            ->willReturn($entity);

        $formMock = $this->createMock(FormInterface::class);
        $formMock->expects($this->once())
            ->method('getParent')
            ->willReturn($parentFormMock);

        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);

        if ($shouldSetData) {
            $this->taggableHelper->expects($this->once())
                ->method('isTaggable')
                ->willReturn(true);
            $this->manager->expects($this->once())
                ->method('getPreparedArray')
                ->with($entity)
                ->willReturn([['owner' => false], ['owner' => true]]);

            $eventMock->expects($this->exactly($shouldSetData))
                ->method('setData');
        } else {
            $this->manager->expects($this->never())
                ->method('getPreparedArray');
            $eventMock->expects($this->never())
                ->method('setData');
        }

        $this->subscriber->preSet($eventMock);
    }

    public function entityProvider(): array
    {
        return [
            'instance of taggable' => [$this->createMock(Taggable::class), true],
            'another entity' => [$this->createMock(Entity::class), false],
        ];
    }
}
