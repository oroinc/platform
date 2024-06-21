<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CollectionTypeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private CollectionTypeSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new CollectionTypeSubscriber();
    }

    private function createEvent(mixed $data, FormInterface $form = null): FormEvent
    {
        return new FormEvent(
            $form ?? $this->createMock(FormInterface::class),
            $data
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::SUBMIT     => 'submit',
                FormEvents::PRE_SUBMIT => 'preSubmit'
            ],
            CollectionTypeSubscriber::getSubscribedEvents()
        );
    }

    public function testSubmitForCollection(): void
    {
        $itemEmpty = $this->createMock(EmptyItem::class);
        $itemEmpty->expects(self::once())
            ->method('isEmpty')
            ->willReturn(true);
        $itemNotEmpty = $this->createMock(EmptyItem::class);
        $itemNotEmpty->expects(self::once())
            ->method('isEmpty')
            ->willReturn(false);
        $itemNotEmptyType = $this->createMock(\stdClass::class);
        $itemNotEmptyType->expects(self::never())
            ->method(self::anything());

        $data = new ArrayCollection([$itemEmpty, $itemNotEmpty, $itemNotEmptyType]);
        $this->subscriber->submit($this->createEvent($data));

        self::assertSame([1 => $itemNotEmpty, 2 => $itemNotEmptyType], $data->toArray());
    }

    public function testSubmitForArray(): void
    {
        $event = $this->createEvent(['', 'item1', null, 'item2']);
        $this->subscriber->submit($event);

        self::assertSame([1 => 'item1', 3 => 'item2'], $event->getData());
    }

    public function testSubmitForArrayWithoutNotEmptyItems(): void
    {
        $event = $this->createEvent(['']);
        $this->subscriber->submit($event);

        self::assertSame([], $event->getData());
    }

    public function testSubmitNotSupportedData(): void
    {
        $data = $this->createMock(\stdClass::class);
        $data->expects(self::never())
            ->method(self::anything());

        $this->subscriber->submit($this->createEvent($data));
    }

    /**
     * @dataProvider preSubmitNoDataDataProvider
     */
    public function testPreSubmitNoData(?array $data): void
    {
        $event = $this->createEvent($data);
        $this->subscriber->preSubmit($event);

        self::assertSame($data, $event->getData());
    }

    public function preSubmitNoDataDataProvider(): array
    {
        return [
            [null],
            [[]]
        ];
    }

    public function testPreSubmitWithIgnorePrimaryBehaviour(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getOption')
            ->with('handle_primary')
            ->willReturn(false);

        $data = [['field1' => 'test']];

        $event = $this->createEvent($data, $form);
        $this->subscriber->preSubmit($event);

        self::assertSame($data, $event->getData());
    }

    /**
     * @dataProvider preSubmitDataProvider
     */
    public function testPreSubmit(array $data, array $expected): void
    {
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getOption')
            ->with('handle_primary')
            ->willReturn(true);

        $event = $this->createEvent($data, $form);
        $this->subscriber->preSubmit($event);

        self::assertSame($expected, $event->getData());
    }

    public function preSubmitDataProvider(): array
    {
        return [
            'set_primary_for_new_data'      => [
                'data'     => [['k' => 'v']],
                'expected' => [['k' => 'v', 'primary' => true]],
            ],
            'set_primary_for_one_item'      => [
                'data'     => [['k' => 'v']],
                'expected' => [['k' => 'v', 'primary' => true]],
            ],
            'not_set_primary_for_two_items' => [
                'data'     => [['k' => 'v'], ['k2' => 'v2']],
                'expected' => [['k' => 'v'], ['k2' => 'v2']],
            ],
            'primary_is_already_set'        => [
                'data'     => [['primary' => true], [['k' => 'v']]],
                'expected' => [['primary' => true], [['k' => 'v']]]
            ],
            'skip_empty_data_array'         => [
                'data'     => [[[]], [], ['k' => 'v', 'primary' => true], []],
                'expected' => ['2' => ['k' => 'v', 'primary' => true]]
            ]
        ];
    }
}
