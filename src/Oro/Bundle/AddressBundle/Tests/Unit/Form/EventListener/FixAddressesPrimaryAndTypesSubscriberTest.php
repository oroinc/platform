<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\EventListener;

use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimaryAndTypesSubscriber;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddress;

class FixAddressesPrimaryAndTypesSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FixAddressesPrimaryAndTypesSubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        $this->subscriber = new FixAddressesPrimaryAndTypesSubscriber('owner.addresses');
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
            $this->assertEquals($expectedData['isPrimary'], $address->isPrimary());
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
                    'foo' => $this->createAddress()->setPrimary(true)->addType($billing),
                    'bar' => $this->createAddress()->addType($billing),
                    'baz' => $this->createAddress()->setPrimary(true)->addType($shipping),
                ),
                'formAddressKey' => 'foo',
                'expectedAddressesData' => array(
                    'foo' => array('isPrimary' => true, 'typeNames' => array('billing')),
                    'bar' => array('isPrimary' => false, 'typeNames' => array()),
                    'baz' => array('isPrimary' => false, 'typeNames' => array('shipping'))
                )
            ),
            'nothing_to_do' => array(
                'allAddresses' => array(
                    'foo' => $this->createAddress(),
                    'bar' => $this->createAddress()->addType($billing),
                    'baz' => $this->createAddress()->setPrimary(true)->addType($shipping),
                ),
                'formAddressKey' => 'foo',
                'expectedAddressesData' => array(
                    'foo' => array('isPrimary' => false, 'typeNames' => array()),
                    'bar' => array('isPrimary' => false, 'typeNames' => array('billing')),
                    'baz' => array('isPrimary' => true, 'typeNames' => array('shipping'))
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
