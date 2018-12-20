<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\EventListener\ItemIdentifierCollectionTypeSubscriber;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class ItemIdentifierCollectionTypeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ItemIdentifierCollectionTypeSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        $this->subscriber = new ItemIdentifierCollectionTypeSubscriber('iso2Code');
    }

    /**
     * @param array $existingData
     * @param mixed $submittedData
     *
     * @return FormEvent
     */
    private function getFormEvent(array $existingData, $submittedData)
    {
        $collection = new ArrayCollection($existingData);
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($collection);

        return new FormEvent($form, $submittedData);
    }

    public function testGetSubscribedEvents()
    {
        self::assertEquals(
            [
                FormEvents::PRE_SUBMIT => ['preSubmit', -10]
            ],
            ItemIdentifierCollectionTypeSubscriber::getSubscribedEvents()
        );
    }

    public function testNoSubmittedData()
    {
        $event = $this->getFormEvent([new Country('c1')], null);

        $this->subscriber->preSubmit($event);

        self::assertNull($event->getData());
    }

    public function testEmptySubmittedData()
    {
        $event = $this->getFormEvent([new Country('c1')], []);

        $this->subscriber->preSubmit($event);

        self::assertSame([], $event->getData());
    }

    public function testEmptyExistingData()
    {
        $submittedData = [
            ['name' => 'item1'],
        ];
        $event = $this->getFormEvent(
            [],
            $submittedData
        );

        $this->subscriber->preSubmit($event);

        self::assertEquals($submittedData, $event->getData());
    }

    public function testAtLeastOneItemInSubmittedDataHasId()
    {
        $submittedData = [
            ['name' => 'item1'],
            ['name' => 'item2', 'iso2Code' => 'c2'],
        ];
        $event = $this->getFormEvent(
            [new Country('c1')],
            $submittedData
        );

        $this->subscriber->preSubmit($event);

        self::assertEquals($submittedData, $event->getData());
    }

    public function testAddItem()
    {
        $submittedData = [
            ['name' => 'item1'],
            ['name' => 'item2'],
        ];
        $event = $this->getFormEvent(
            [new Country('c1')],
            $submittedData
        );

        $this->subscriber->preSubmit($event);

        self::assertEquals(
            [
                ['name' => 'item1', 'iso2Code' => 'c1'],
                ['name' => 'item2'],
            ],
            $event->getData()
        );
    }

    public function testRemoveItem()
    {
        $submittedData = [
            ['name' => 'item1'],
        ];
        $event = $this->getFormEvent(
            [new Country('c1'), new Country('c2')],
            $submittedData
        );

        $this->subscriber->preSubmit($event);

        self::assertEquals(
            [
                ['name' => 'item1', 'iso2Code' => 'c1'],
            ],
            $event->getData()
        );
    }

    public function testUpdateItem()
    {
        $submittedData = [
            ['name' => 'item1'],
            ['name' => 'item2'],
        ];
        $event = $this->getFormEvent(
            [new Country('c1'), new Country('c2')],
            $submittedData
        );

        $this->subscriber->preSubmit($event);

        self::assertEquals(
            [
                ['name' => 'item1', 'iso2Code' => 'c1'],
                ['name' => 'item2', 'iso2Code' => 'c2'],
            ],
            $event->getData()
        );
    }

    public function testRemoveNotLastItem()
    {
        $submittedData = [
            ['name' => 'item2'],
        ];
        $event = $this->getFormEvent(
            [new Country('c1'), new Country('c2')],
            $submittedData
        );

        $this->subscriber->preSubmit($event);

        self::assertEquals(
            [
                ['name' => 'item2', 'iso2Code' => 'c1'],
            ],
            $event->getData()
        );
    }
}
