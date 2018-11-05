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
    /**
     * @var FixAddressesPrimarySubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        $this->subscriber = new FixAddressesPrimarySubscriber('owner.addresses');
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            array(FormEvents::POST_SUBMIT => 'postSubmit'),
            $this->subscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider postSubmitDataProvider
     */
    public function testPostSubmit(array $allAddresses, $formAddressKey, array $expectedAddressesData)
    {
        $owner = new TypedAddressOwner($allAddresses);
        $event = new FormEvent($this->createMock(FormInterface::class), $allAddresses[$formAddressKey]);

        $this->subscriber->postSubmit($event);

        foreach ($expectedAddressesData as $addressKey => $expectedData) {
            /** @var AbstractTypedAddress $address */
            $address = $allAddresses[$addressKey];
            $this->assertEquals($expectedData['isPrimary'], $address->isPrimary());
        }
    }

    public function postSubmitDataProvider()
    {
        return array(
            'reset_other_primary' => array(
                'allAddresses' => array(
                    'foo' => $this->createAddress()->setPrimary(true),
                    'bar' => $this->createAddress(),
                    'baz' => $this->createAddress()->setPrimary(true),
                ),
                'formAddressKey' => 'foo',
                'expectedAddressesData' => array(
                    'foo' => array('isPrimary' => true),
                    'bar' => array('isPrimary' => false),
                    'baz' => array('isPrimary' => false)
                )
            ),
            'set_primary' => array(
                'allAddresses' => array(
                    'foo' => $this->createAddress(),
                    'bar' => $this->createAddress(),
                    'baz' => $this->createAddress()->setPrimary(true),
                ),
                'formAddressKey' => 'foo',
                'expectedAddressesData' => array(
                    'foo' => array('isPrimary' => false),
                    'bar' => array('isPrimary' => false),
                    'baz' => array('isPrimary' => true)
                )
            ),
        );
    }

    /**
     * @return TypedAddress
     */
    protected function createAddress()
    {
        return new TypedAddress();
    }
}
