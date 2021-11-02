<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;

class AbstractTypedAddressTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractTypedAddress */
    private $address;

    protected function setUp(): void
    {
        $this->address = $this->getMockForAbstractClass(AbstractTypedAddress::class);
    }

    public function testAddType()
    {
        $this->assertEmpty($this->address->getTypes()->toArray());

        $type = new AddressType('testAddressType');

        // add type in first time
        $this->address->addType($type);
        $types = $this->address->getTypes();
        $this->assertCount(1, $types);
        $this->assertContains($type, $types);

        // type should be added only once
        $this->address->addType($type);
        $types = $this->address->getTypes();
        $this->assertCount(1, $types);
        $this->assertContains($type, $types);
    }

    public function testGetTypeNames()
    {
        $this->assertSame([], $this->address->getTypeNames());

        $this->address->addType(new AddressType('billing'));
        $this->address->addType(new AddressType('shipping'));

        $this->assertEquals(['billing', 'shipping'], $this->address->getTypeNames());
    }

    public function testGetTypeLabels()
    {
        $this->assertSame([], $this->address->getTypeLabels());

        $billing = new AddressType('billing');
        $billing->setLabel('Billing');
        $this->address->addType($billing);

        $shipping = new AddressType('shipping');
        $shipping->setLabel('Shipping');
        $this->address->addType($shipping);

        $this->assertEquals(['Billing', 'Shipping'], $this->address->getTypeLabels());
    }

    public function testGetTypeByName()
    {
        $addressType = new AddressType('billing');
        $this->address->addType($addressType);

        $this->assertSame($addressType, $this->address->getTypeByName('billing'));
        $this->assertNull($this->address->getTypeByName('shipping'));
    }

    public function testHasTypeWithName()
    {
        $this->address->addType(new AddressType('billing'));

        $this->assertTrue($this->address->hasTypeWithName('billing'));
        $this->assertFalse($this->address->hasTypeWithName('shipping'));
    }

    public function testPrimary()
    {
        $this->assertFalse($this->address->isPrimary());

        $this->address->setPrimary(true);

        $this->assertTrue($this->address->isPrimary());
    }

    public function testRemoveType()
    {
        $type = new AddressType('testAddressType');
        $this->address->addType($type);
        $this->assertCount(1, $this->address->getTypes());

        $this->address->removeType($type);
        $this->assertEmpty($this->address->getTypes()->toArray());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->address->isEmpty());
        $this->address->setPrimary(true);
        $this->assertFalse($this->address->isEmpty());
        $this->address->setPrimary(false);
        $this->address->addType(new AddressType('billing'));
        $this->assertFalse($this->address->isEmpty());
    }
}
