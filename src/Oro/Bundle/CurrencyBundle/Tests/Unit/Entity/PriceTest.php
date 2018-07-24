<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PriceTest extends \PHPUnit\Framework\TestCase
{
    const VALUE = 100;
    const CURRENCY = 'USD';

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Price();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        return [
            ['value', self::VALUE],
            ['currency', self::CURRENCY]
        ];
    }

    public function testCreate()
    {
        $price = Price::create(self::VALUE, self::CURRENCY);
        $this->assertInstanceOf('Oro\Bundle\CurrencyBundle\Entity\Price', $price);
        $this->assertEquals(self::VALUE, $price->getValue());
        $this->assertEquals(self::CURRENCY, $price->getCurrency());
    }
}
