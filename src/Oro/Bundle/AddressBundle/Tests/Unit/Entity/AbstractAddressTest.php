<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, $value)
    {
        $address = $this->createAddress();

        call_user_func_array([$address, 'set' . ucfirst($property)], [$value]);
        $this->assertEquals($value, call_user_func_array([$address, 'get' . ucfirst($property)], []));
    }

    public function propertiesDataProvider(): array
    {
        $country = $this->createMock(Country::class);
        $region = $this->createMock(Region::class);
        $createdDateTime = new \DateTime();

        return [
            'country'      => ['country', $country],
            'city'         => ['city', 'city'],
            'created'      => ['created', $createdDateTime],
            'firstName'    => ['firstName', 'first_name'],
            'label'        => ['label', 'Shipping'],
            'lastName'     => ['lastName', 'last name'],
            'middleName'   => ['middleName', 'middle name'],
            'namePrefix'   => ['namePrefix', 'name prefix'],
            'nameSuffix'   => ['nameSuffix', 'name suffix'],
            'organization' => ['organization', 'Oro Inc.'],
            'postalCode'   => ['postalCode', '12345'],
            'region'       => ['region', $region],
            'regionText'   => ['regionText', 'test region'],
            'street'       => ['street', 'street'],
            'street2'      => ['street2', 'street2'],
            'updated'      => ['updated', $createdDateTime],
        ];
    }

    public function testBeforeSave()
    {
        $address = $this->createAddress();

        $this->assertNull($address->getCreated());
        $this->assertNull($address->getUpdated());

        $address->beforeSave();

        $this->assertNotNull($address->getCreated());
        $this->assertNotNull($address->getUpdated());

        $this->assertEqualsWithDelta($address->getCreated(), $address->getUpdated(), 1);
    }

    public function testBeforeUpdate()
    {
        $address = $this->createAddress();

        $this->assertNull($address->getCreated());
        $this->assertNull($address->getUpdated());

        $address->beforeUpdate();

        $this->assertNull($address->getCreated());
        $this->assertNotNull($address->getUpdated());
    }

    public function testGetRegionName()
    {
        $address = $this->createAddress();
        $address->setRegionText('New York');

        $this->assertEquals('New York', $address->getRegionName());

        $region = $this->createMock(Region::class);
        $region->expects($this->once())
            ->method('getName')
            ->willReturn('California');
        $address->setRegion($region);

        $this->assertEquals('California', $address->getRegionName());
    }

    public function testGetRegionCode()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getRegionCode());

        $region = $this->createMock(Region::class);
        $region->expects($this->once())
            ->method('getCode')
            ->willReturn('CA');
        $address->setRegion($region);

        $this->assertEquals('CA', $address->getRegionCode());
    }

    public function testGetCountryName()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getCountryName());

        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('getName')
            ->willReturn('USA');
        $address->setCountry($country);

        $this->assertEquals('USA', $address->getCountryName());
    }

    public function testGetCountryIso2()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getCountryIso2());

        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('getIso2Code')
            ->willReturn('US');
        $address->setCountry($country);

        $this->assertEquals('US', $address->getCountryIso2());
    }

    public function testGetCountryIso3()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getCountryIso2());

        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('getIso3Code')
            ->willReturn('USA');
        $address->setCountry($country);

        $this->assertEquals('USA', $address->getCountryIso3());
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(array $actualData, string $expected)
    {
        $address = $this->createAddress();

        foreach ($actualData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            $address->$setter($value);
        }

        $this->assertTrue(method_exists($address, '__toString'));
        $this->assertEquals($expected, $address->__toString());
    }

    public function toStringDataProvider(): array
    {
        return [
            [
                [
                    'firstName' => 'FirstName',
                    'lastName' => 'LastName',
                    'street' => 'Street',
                    'region' => $this->getRegion('some region'),
                    'postalCode' => '12345',
                    'country' => $this->getCountry('Ukraine'),
                ],
                'FirstName LastName , Street   some region , Ukraine 12345'
            ],
            [
                [
                    'firstName' => '',
                    'lastName' => 'LastName',
                    'street' => 'Street',
                    'region' => $this->getRegion('some region'),
                    'postalCode' => '',
                    'country' => $this->getCountry('Ukraine'),
                ],
                'LastName , Street   some region , Ukraine'
            ],
            [
                [
                    'firstName' => '',
                    'lastName' => '',
                    'street' => '',
                    'region' => '',
                    'postalCode' => '',
                    'country' => '',
                ],
                ''
            ],
        ];
    }

    private function getCountry(string $name): Country
    {
        $result = $this->createMock(Country::class);
        $result->expects($this->any())
            ->method('__toString')
            ->willReturn($name);

        return $result;
    }

    private function getRegion(string $name): Region
    {
        $result = $this->createMock(Region::class);
        $result->expects($this->any())
            ->method('__toString')
            ->willReturn($name);

        return $result;
    }

    public function testRegionText()
    {
        $address = $this->createAddress();

        $region = $this->createMock(Region::class);
        $address->setRegion($region);
        $this->assertEquals($region, $address->getRegion());
        $address->setRegionText('text region');
        $this->assertEquals('text region', $address->getUniversalRegion());
    }

    public function testIsEmpty()
    {
        $address = $this->createAddress();
        $this->assertTrue($address->isEmpty());
    }

    /**
     * @dataProvider emptyCheckPropertiesDataProvider
    */
    public function testIsNotEmpty(string $property, $value)
    {
        $address = $this->createAddress();
        call_user_func_array([$address, 'set' . ucfirst($property)], [$value]);
        $this->assertFalse($address->isEmpty());
    }

    public function emptyCheckPropertiesDataProvider(): array
    {
        $country = $this->createMock(Country::class);
        $region = $this->createMock(Region::class);

        return [
            'lastName' => ['lastName', 'last name'],
            'firstName' => ['firstName', 'first_name'],
            'street' => ['street', 'street'],
            'street2' => ['street2', 'street2'],
            'city' => ['city', 'city'],
            'region' => ['region', $region],
            'regionText' => ['regionText', 'test region'],
            'postalCode' => ['postalCode', '12345'],
            'country' => ['country', $country],
        ];
    }

    /**
     * @dataProvider isEqualDataProvider
     */
    public function testIsEqual(AbstractAddress $one, ?AbstractAddress $two, bool $expectedResult)
    {
        $this->assertEquals($expectedResult, $one->isEqual($two));
    }

    public function isEqualDataProvider(): array
    {
        $one = $this->createAddress();

        return [
            [$one, $one, true],
            [$this->createAddress(100), $this->createAddress(100), true],
            [$this->createAddress(), $this->createAddress(), false],
            [$this->createAddress(100), $this->createAddress(), false],
            [$this->createAddress(), null, false],
        ];
    }

    private function createAddress(int $id = null): AbstractAddress
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        if (null !== $id) {
            ReflectionUtil::setId($address, $id);
        }

        return $address;
    }
}
