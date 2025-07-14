<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use PHPUnit\Framework\TestCase;

class CountryTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testSettersAndGetters(string $property, mixed $value): void
    {
        $obj = new Country('iso2code');
        $obj->{'set' . ucfirst($property)}($value);
        $this->assertEquals($value, call_user_func_array([$obj, 'get' . ucfirst($property)], []));
    }

    public function testConstructorData(): void
    {
        $obj = new Country('iso2Code');

        $this->assertEquals('iso2Code', $obj->getIso2Code());
    }

    public function provider(): array
    {
        return [
            ['name', 'testValue'],
            ['iso3code', 'testValue'],
            ['regions', new ArrayCollection()],
            ['locale', 'testValue'],
        ];
    }

    public function testToString(): void
    {
        $obj = new Country('iso2Code');
        $obj->setName('name');
        $this->assertEquals('name', $obj->__toString());
    }

    public function testAddRegion(): void
    {
        $country = new Country('iso2Code');
        $region = new Region('combinedCode');

        $this->assertEmpty($country->getRegions()->getValues());

        $country->addRegion($region);

        $this->assertEquals([$region], $country->getRegions()->getValues());
        $this->assertEquals($country, $region->getCountry());
    }

    public function testRemoveRegion(): void
    {
        $country = new Country('iso2Code');
        $region = new Region('combinedCode');
        $country->addRegion($region);

        $this->assertNotEmpty($country->getRegions()->getValues());

        $country->removeRegion($region);

        $this->assertEmpty($country->getRegions()->getValues());
        $this->assertNull($region->getCountry());
    }

    public function testHasRegions(): void
    {
        $country = new Country('iso2Code');
        $region = new Region('combinedCode');

        $this->assertFalse($country->hasRegions());

        $country->addRegion($region);

        $this->assertTrue($country->hasRegions());
    }
}
