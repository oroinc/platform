<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Form\DataTransformer\AddressSameTransformer;
use Oro\Component\Testing\Unit\EntityTrait;

class AddressSameTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AddressSameTransformer */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transformer = new AddressSameTransformer(
            $this->getPropertyAccessor(),
            ['billing_address', 'shipping_address']
        );
    }

    public function testTransformWithSameId()
    {
        $address = $this->getEntity(Address::class, [
            'id' => 1,
            'label' => 'Test Address'
        ]);

        $multiAddress = new MultiAddressMock();
        $multiAddress->setBillingAddress($address);
        $multiAddress->setShippingAddress($address);

        $result = new MultiAddressMock();
        $result->setBillingAddress($address);
        $result->setShippingAddress((new Address())->setLabel('Test Address'));

        $this->assertEquals($result, $this->transformer->transform($multiAddress));
    }

    public function testTransformWithDifferentId()
    {
        $address1 = $this->getEntity(Address::class, [
            'id' => 1,
            'label' => 'Test Address 1'
        ]);

        $address2 = $this->getEntity(Address::class, [
            'id' => 2,
            'label' => 'Test Address 2'
        ]);

        $multiAddress = new MultiAddressMock();
        $multiAddress->setBillingAddress($address1);
        $multiAddress->setShippingAddress($address2);

        $this->assertEquals($multiAddress, $this->transformer->transform($multiAddress));
    }

    public function testTransformNull()
    {
        $multiAddress = null;
        $this->assertNull($this->transformer->transform($multiAddress));
    }

    public function testTransformInvalidArgument()
    {
        $multiAddress = new MultiAddressMock();
        $this->assertEquals($multiAddress, $this->transformer->transform($multiAddress));
    }

    public function testReverseTransform()
    {
        $this->assertEquals('value', $this->transformer->reverseTransform('value'));
    }
}
