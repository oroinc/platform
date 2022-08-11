<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;
use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimarySubscriber;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddress;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class FixAddressesPrimarySubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var FixAddressesPrimarySubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new FixAddressesPrimarySubscriber('owner.addresses');
    }

    private function createAddress(bool $primary = false): TypedAddress
    {
        $address = new TypedAddress();
        $address->setPrimary($primary);

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
            /** @var AbstractTypedAddress $address */
            $address = $allAddresses[$addressKey];
            $this->assertEquals($expectedData['isPrimary'], $address->isPrimary());
        }
    }

    public function postSubmitDataProvider(): array
    {
        return [
            'reset_other_primary' => [
                'allAddresses' => [
                    'foo' => $this->createAddress(true),
                    'bar' => $this->createAddress(),
                    'baz' => $this->createAddress(true),
                ],
                'formAddressKey' => 'foo',
                'expectedAddressesData' => [
                    'foo' => ['isPrimary' => true],
                    'bar' => ['isPrimary' => false],
                    'baz' => ['isPrimary' => false]
                ]
            ],
            'set_primary' => [
                'allAddresses' => [
                    'foo' => $this->createAddress(),
                    'bar' => $this->createAddress(),
                    'baz' => $this->createAddress(true),
                ],
                'formAddressKey' => 'foo',
                'expectedAddressesData' => [
                    'foo' => ['isPrimary' => false],
                    'bar' => ['isPrimary' => false],
                    'baz' => ['isPrimary' => true]
                ]
            ],
        ];
    }
}
