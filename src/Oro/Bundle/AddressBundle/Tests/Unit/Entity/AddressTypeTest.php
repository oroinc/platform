<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use PHPUnit\Framework\TestCase;

class AddressTypeTest extends TestCase
{
    private AddressType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new AddressType('billing');
    }

    public function testName(): void
    {
        $this->assertEquals('billing', $this->type->getName());
    }

    public function testLabel(): void
    {
        $this->assertNull($this->type->getLabel());

        $this->type->setLabel('Billing');

        $this->assertEquals('Billing', $this->type->getLabel());
    }

    public function testLocale(): void
    {
        $this->assertNull($this->type->getLocale());

        $this->type->setLocale('en');

        $this->assertEquals('en', $this->type->getLocale());
    }

    public function testToString(): void
    {
        $this->assertEquals('', $this->type);

        $this->type->setLabel('Shipping');

        $this->assertEquals('Shipping', (string)$this->type);
    }
}
