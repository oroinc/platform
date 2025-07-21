<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Form\DataTransformer\AddressSameTransformer;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class AddressSameTransformerTest extends TestCase
{
    private AddressSameTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new AddressSameTransformer(
            PropertyAccess::createPropertyAccessor(),
            ['billing_address', 'shipping_address']
        );
    }

    private function getAddress(int $id, string $label): Address
    {
        $address = new Address();
        ReflectionUtil::setId($address, $id);
        $address->setLabel($label);

        return $address;
    }

    public function testTransformWithSameId(): void
    {
        $address = $this->getAddress(1, 'Test Address');

        $multiAddress = new MultiAddressMock();
        $multiAddress->setBillingAddress($address);
        $multiAddress->setShippingAddress($address);

        $result = new MultiAddressMock();
        $result->setBillingAddress($address);
        $result->setShippingAddress((new Address())->setLabel('Test Address'));

        $this->assertEquals($result, $this->transformer->transform($multiAddress));
    }

    public function testTransformWithDifferentId(): void
    {
        $address1 = $this->getAddress(1, 'Test Address 1');
        $address2 = $this->getAddress(2, 'Test Address 2');

        $multiAddress = new MultiAddressMock();
        $multiAddress->setBillingAddress($address1);
        $multiAddress->setShippingAddress($address2);

        $this->assertEquals($multiAddress, $this->transformer->transform($multiAddress));
    }

    public function testTransformNull(): void
    {
        $multiAddress = null;
        $this->assertNull($this->transformer->transform($multiAddress));
    }

    public function testTransformInvalidArgument(): void
    {
        $multiAddress = new MultiAddressMock();
        $this->assertEquals($multiAddress, $this->transformer->transform($multiAddress));
    }

    public function testReverseTransform(): void
    {
        $this->assertEquals('value', $this->transformer->reverseTransform('value'));
    }
}
