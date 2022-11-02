<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class CollectionTypeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CollectionTypeSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new CollectionTypeSubscriber();
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->subscriber->getSubscribedEvents();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(FormEvents::POST_SUBMIT, $result);
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $result);
    }

    public function testPostSubmit()
    {
        $itemEmpty = $this->createMock(EmptyItem::class);
        $itemEmpty->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $itemNotEmpty = $this->createMock(EmptyItem::class);
        $itemNotEmpty->expects($this->once())
            ->method('isEmpty')
            ->willReturn(false);
        $itemNotEmptyType = $this->createMock(\stdClass::class);
        $itemNotEmptyType->expects($this->never())
            ->method($this->anything());

        $data = new ArrayCollection([$itemEmpty, $itemNotEmpty, $itemNotEmptyType]);
        $this->subscriber->postSubmit($this->createEvent($data));

        $this->assertEquals(
            [
                1 => $itemNotEmpty,
                2 => $itemNotEmptyType
            ],
            $data->toArray()
        );
    }

    public function testPostSubmitNotCollectionData()
    {
        $data = $this->createMock(\stdClass::class);
        $data->expects($this->never())
            ->method($this->anything());

        $this->subscriber->postSubmit($this->createEvent($data));
    }

    /**
     * @dataProvider preSubmitNoDataDataProvider
     */
    public function testPreSubmitNoData(?array $data)
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSubmit($event);
    }

    public function preSubmitNoDataDataProvider(): array
    {
        return [
            [
                null, []
            ],
            [
                [], []
            ]
        ];
    }

    public function testPreSubmitWithIgnorePrimaryBehaviour()
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('handle_primary')
            ->willReturn(false);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn([['field1' => 'test']]);
        $event->expects($this->never())
            ->method('setData');

        $this->subscriber->preSubmit($event);
    }

    /**
     * @dataProvider preSubmitDataProvider
     */
    public function testPreSubmit(array $data, array $expected)
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('handle_primary')
            ->willReturn(true);

        $event = $this->createEvent($data, $form);
        $this->subscriber->preSubmit($event);
        $this->assertEquals($expected, $event->getData());
    }

    public function preSubmitDataProvider(): array
    {
        return [
            'set_primary_for_new_data' => [
                'data' => [['k' => 'v']],
                'expected' => [['k' => 'v', 'primary' => true]],
            ],
            'set_primary_for_one_item' => [
                'data' => [['k' => 'v']],
                'expected' => [['k' => 'v', 'primary' => true]],
            ],
            'not_set_primary_for_two_items' => [
                'data' => [['k' => 'v'], ['k2' => 'v2']],
                'expected' => [['k' => 'v'], ['k2' => 'v2']],
            ],
            'primary_is_already_set' => [
                'data' => [['primary' => true], [['k' => 'v']]],
                'expected' => [['primary' => true], [['k' => 'v']]]
            ],
            'skip_empty_data_array' => [
                'data' => [[[]], [], ['k' => 'v', 'primary' => true], []],
                'expected' => ['2' => ['k' => 'v', 'primary' => true]]
            ]
        ];
    }

    /**
     * @dataProvider preSubmitNoResetDataProvider
     */
    public function testPreSubmitNoReset(array|string $data)
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $event->expects($this->never())
            ->method('setData');
        $this->subscriber->preSubmit($event);
    }

    public function preSubmitNoResetDataProvider(): array
    {
        return [
            [[]],
            ['foo']
        ];
    }

    private function createEvent(mixed $data, FormInterface $form = null): FormEvent
    {
        return new FormEvent(
            $form ?? $this->createMock(FormInterface::class),
            $data
        );
    }
}
