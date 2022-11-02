<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesTypesSubscriber;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddress;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class FixAddressesTypesSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var FixAddressesTypesSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new FixAddressesTypesSubscriber('owner.addresses');
    }

    private function createAddress(AddressType $type = null): TypedAddress
    {
        $address = new TypedAddress();
        if (null !== $type) {
            $address->addType($type);
        }

        return $address;
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::POST_SUBMIT => 'postSubmit'],
            $this->subscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider postSubmitDataProvider
     */
    public function testPostSubmit(array $allAddresses, $formAddressKey, array $expectedAddressesData)
    {
        $owner = new TypedAddressOwner();
        foreach ($allAddresses as $address) {
            $address->setOwner($owner);
            $owner->getAddresses()->add($address);
        }
        $event = new FormEvent($this->createMock(FormInterface::class), $allAddresses[$formAddressKey]);

        $this->subscriber->postSubmit($event);

        foreach ($expectedAddressesData as $addressKey => $expectedData) {
            $address = $allAddresses[$addressKey];
            $this->assertEquals($expectedData['typeNames'], $address->getTypeNames());
        }
    }

    public function postSubmitDataProvider(): array
    {
        $billing = new AddressType('billing');
        $shipping = new AddressType('shipping');

        return [
            'unset_primary_and_remove_type' => [
                'allAddresses' => [
                    'foo' => $this->createAddress($billing),
                    'bar' => $this->createAddress($billing),
                    'baz' => $this->createAddress($shipping),
                ],
                'formAddressKey' => 'foo',
                'expectedAddressesData' => [
                    'foo' => ['typeNames' => ['billing']],
                    'bar' => ['typeNames' => []],
                    'baz' => ['typeNames' => ['shipping']]
                ]
            ],
            'nothing_to_do' => [
                'allAddresses' => [
                    'foo' => $this->createAddress(),
                    'bar' => $this->createAddress($billing),
                    'baz' => $this->createAddress($shipping),
                ],
                'formAddressKey' => 'foo',
                'expectedAddressesData' => [
                    'foo' => ['typeNames' => []],
                    'bar' => ['typeNames' => ['billing']],
                    'baz' => ['typeNames' => ['shipping']]
                ]
            ],
        ];
    }
}
