<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CommentBundle\Form\EventListener\CommentSubscriber;
use Symfony\Component\Form\FormEvents;

class CommentSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CommentSubscriber */
    protected $subscriber;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /**
     * SetUp test environment
     */
    protected function setUp()
    {
        $this->subscriber = new CommentSubscriber();
        $this->event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->subscriber);
        unset($this->form);
        unset($this->event);
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->subscriber->getSubscribedEvents();

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
    }

    /**
     * @dataProvider getTestData
     *
     * @param bool $has
     * @param int $removeCount
     */
    public function testPreSetData($has, $removeCount)
    {
        $this->form->expects($this->once())
            ->method('has')
            ->with('owner')
            ->will($this->returnValue($has));
        $this->form->expects($this->exactly($removeCount))
            ->method('remove')
            ->with('owner');
        $this->event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($this->form));
        $this->subscriber->preSetData($this->event);
    }

    public function getTestData()
    {
        return [
            'with owner' => [true, 1],
            'without owner' => [false, 0],
        ];
    }
}
