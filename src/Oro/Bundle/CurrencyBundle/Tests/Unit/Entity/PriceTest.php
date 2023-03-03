<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

class PriceTest extends \PHPUnit\Framework\TestCase
{
    private const VALUE = 100;
    private const CURRENCY = 'USD';

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new Price();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['value', self::VALUE],
            ['currency', self::CURRENCY]
        ];
    }

    public function testCreate()
    {
        $price = Price::create(self::VALUE, self::CURRENCY);
        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(self::VALUE, $price->getValue());
        $this->assertEquals(self::CURRENCY, $price->getCurrency());
    }
}
