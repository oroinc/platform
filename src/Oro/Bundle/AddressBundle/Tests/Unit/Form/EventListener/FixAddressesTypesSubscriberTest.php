<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\EventListener;

use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesTypesSubscriber;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddress;

use Symfony\Component\Form\FormEvents;

class FixAddressesTypesSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FixAddressesTypesSubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        $this->subscriber = new FixAddressesTypesSubscriber('owner.addresses');
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

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->setMethods(array('getData'))
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($allAddresses[$formAddressKey]));

        $this->subscriber->postSubmit($event);

        foreach ($expectedAddressesData as $addressKey => $expectedData) {
            $address = $allAddresses[$addressKey];
            $this->assertEquals($expectedData['typeNames'], $address->getTypeNames());
        }
    }

    public function postSubmitDataProvider()
    {
        $billing = new AddressType('billing');
        $shipping = new AddressType('shipping');

        return array(
            'unset_primary_and_remove_type' => array(
                'allAddresses' => array(
                    'foo' => $this->createAddress()->addType($billing),
                    'bar' => $this->createAddress()->addType($billing),
                    'baz' => $this->createAddress()->addType($shipping),
                ),
                'formAddressKey' => 'foo',
                'expectedAddressesData' => array(
                    'foo' => array('typeNames' => array('billing')),
                    'bar' => array('typeNames' => array()),
                    'baz' => array('typeNames' => array('shipping'))
                )
            ),
            'nothing_to_do' => array(
                'allAddresses' => array(
                    'foo' => $this->createAddress(),
                    'bar' => $this->createAddress()->addType($billing),
                    'baz' => $this->createAddress()->addType($shipping),
                ),
                'formAddressKey' => 'foo',
                'expectedAddressesData' => array(
                    'foo' => array('typeNames' => array()),
                    'bar' => array('typeNames' => array('billing')),
                    'baz' => array('typeNames' => array('shipping'))
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
