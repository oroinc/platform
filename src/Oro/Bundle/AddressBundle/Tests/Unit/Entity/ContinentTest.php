<?php

namespace Oro\Bundle\AddressBundle\Tests\Entity;

use Oro\Bundle\AddressBundle\Entity\Continent;
use Oro\Bundle\AddressBundle\Entity\Country;

class ContinentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     * @param string $property
     */
    public function testSettersAndGetters($property)
    {
        $obj = new Continent('iso2code');
        $value = 'testValue';

        call_user_func_array(array($obj, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($value, call_user_func_array(array($obj, 'get' . ucfirst($property)), array()));
    }

    public function testConstructorData()
    {
        $obj = new Continent('iso2Code');

        $this->assertEquals('iso2Code', $obj->getCode());
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array('name'),
        );
    }

    public function testToString()
    {
        $obj = new Continent('iso2Code');
        $obj->setName('name');
        $this->assertEquals('name', $obj->__toString());
    }

    public function testAddCountry()
    {
        $continent = new Continent('iso2Code');
        $country = new Country('iso2Code');

        $this->assertEmpty($continent->getCountries()->getValues());

        $continent->addCountry($country);

        $this->assertEquals(array($country), $continent->getCountries()->getValues());
        $this->assertEquals($continent, $country->getContinent());
    }

    public function testRemoveCountry()
    {
        $continent = new Continent('iso2Code');
        $country = new Country('iso2Code');
        $continent->addCountry($country);

        $this->assertNotEmpty($continent->getCountries()->getValues());

        $continent->removeCountry($country);

        $this->assertEmpty($continent->getCountries()->getValues());
        $this->assertNull($country->getContinent());
    }
}
