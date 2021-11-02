<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

class RegionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRegionCombinedCode()
    {
        $this->assertEquals('US-CA', Region::getRegionCombinedCode('US', 'CA'));
    }

    public function testConstructorData()
    {
        $combinedCode = 'combinedCode';

        $obj = new Region($combinedCode);
        $this->assertEquals($combinedCode, $obj->getCombinedCode());
    }

    public function testCountrySetter()
    {
        $countryMock = $this->createMock(Country::class);

        $obj = new Region('combinedCode');
        $obj->setCountry($countryMock);

        $this->assertEquals($countryMock, $obj->getCountry());
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property)
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

    public function testToString()
    {
        $obj = new Region('combinedCode');
        $obj->setName('name');
        $this->assertEquals('name', $obj->__toString());
    }
}
