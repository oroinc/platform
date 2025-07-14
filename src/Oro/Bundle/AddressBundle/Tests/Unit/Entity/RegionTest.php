<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use PHPUnit\Framework\TestCase;

class RegionTest extends TestCase
{
    public function testGetRegionCombinedCode(): void
    {
        $this->assertEquals('US-CA', Region::getRegionCombinedCode('US', 'CA'));
    }

    public function testConstructorData(): void
    {
        $combinedCode = 'combinedCode';

        $obj = new Region($combinedCode);
        $this->assertEquals($combinedCode, $obj->getCombinedCode());
    }

    public function testCountrySetter(): void
    {
        $countryMock = $this->createMock(Country::class);

        $obj = new Region('combinedCode');
        $obj->setCountry($countryMock);

        $this->assertEquals($countryMock, $obj->getCountry());
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property): void
    {
        $obj = new Region('combinedCode');
        $value = 'testValue';

        call_user_func_array([$obj, 'set' . ucfirst($property)], [$value]);
        $this->assertEquals($value, call_user_func_array([$obj, 'get' . ucfirst($property)], []));
    }

    public function settersAndGettersDataProvider(): array
    {
        return [
            ['name'],
            ['code'],
            ['locale'],
        ];
    }

    public function testToString(): void
    {
        $obj = new Region('combinedCode');
        $obj->setName('name');
        $this->assertEquals('name', $obj->__toString());
    }
}
