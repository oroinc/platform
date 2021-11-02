<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CommentBundle\Form\EventListener\CommentSubscriber;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CommentSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CommentSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new CommentSubscriber();
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->subscriber->getSubscribedEvents();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
    }

    /**
     * @dataProvider getTestData
     */
    public function testPreSetData(bool $has, int $removeCount)
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('has')
            ->with('owner')
            ->willReturn($has);
        $form->expects($this->exactly($removeCount))
            ->method('remove')
            ->with('owner');

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->subscriber->preSetData($event);
    }

    public function getTestData(): array
    {
        return [
            'with owner' => [true, 1],
            'without owner' => [false, 0],
        ];
    }
}
