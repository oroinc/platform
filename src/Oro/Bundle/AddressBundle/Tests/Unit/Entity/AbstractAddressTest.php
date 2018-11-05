<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AbstractAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $address = $this->createAddress();

        call_user_func_array(array($address, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($value, call_user_func_array(array($address, 'get' . ucfirst($property)), array()));
    }

    /**
     * Data provider with entity properties
     *
     * @return array
     */
    public function propertiesDataProvider()
    {
        $countryMock = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->getMock();

        $regionMock = $this->createMock('Oro\Bundle\AddressBundle\Entity\Region', array(), array('combinedCode'));

        $createdDateTime = new \DateTime();

        return array(
            'country'      => array('country', $countryMock),
            'city'         => array('city', 'city'),
            'created'      => array('created', $createdDateTime),
            'firstName'    => array('firstName', 'first_name'),
            'id'           => array('id', 1),
            'label'        => array('label', 'Shipping'),
            'lastName'     => array('lastName', 'last name'),
            'middleName'   => array('middleName', 'middle name'),
            'namePrefix'   => array('namePrefix', 'name prefix'),
            'nameSuffix'   => array('nameSuffix', 'name suffix'),
            'organization' => array('organization', 'Oro Inc.'),
            'postalCode'   => array('postalCode', '12345'),
            'region'       => array('region', $regionMock),
            'regionText'   => array('regionText', 'test region'),
            'street'       => array('street', 'street'),
            'street2'      => array('street2', 'street2'),
            'updated'      => array('updated', $createdDateTime),
        );
    }

    public function testBeforeSave()
    {
        $address = $this->createAddress();

        $this->assertNull($address->getCreated());
        $this->assertNull($address->getUpdated());

        $address->beforeSave();

        $this->assertNotNull($address->getCreated());
        $this->assertNotNull($address->getUpdated());

        $this->assertEquals($address->getCreated(), $address->getUpdated(), '', 1);
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

        /** @var \PHPUnit\Framework\MockObject\MockObject|Region $region */
        $region = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Region')
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();
        $region->expects($this->once())->method('getName')->will($this->returnValue('California'));
        $address->setRegion($region);

        $this->assertEquals('California', $address->getRegionName());
    }

    public function testGetRegionCode()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getRegionCode());

        /** @var \PHPUnit\Framework\MockObject\MockObject|Region $region */
        $region = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Region')
            ->disableOriginalConstructor()
            ->setMethods(array('getCode'))
            ->getMock();
        $region->expects($this->once())->method('getCode')->will($this->returnValue('CA'));
        $address->setRegion($region);

        $this->assertEquals('CA', $address->getRegionCode());
    }

    public function testGetCountryName()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getCountryName());

        /** @var \PHPUnit\Framework\MockObject\MockObject|Country $country */
        $country = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();
        $country->expects($this->once())->method('getName')->will($this->returnValue('USA'));
        $address->setCountry($country);

        $this->assertEquals('USA', $address->getCountryName());
    }

    public function testGetCountryIso2()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getCountryIso2());

        /** @var \PHPUnit\Framework\MockObject\MockObject|Country $country */
        $country = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->setMethods(array('getIso2Code'))
            ->getMock();
        $country->expects($this->once())->method('getIso2Code')->will($this->returnValue('US'));
        $address->setCountry($country);

        $this->assertEquals('US', $address->getCountryIso2());
    }

    public function testGetCountryIso3()
    {
        $address = $this->createAddress();

        $this->assertEquals('', $address->getCountryIso2());

        /** @var \PHPUnit\Framework\MockObject\MockObject|Country $country */
        $country = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->setMethods(array('getIso3Code'))
            ->getMock();
        $country->expects($this->once())->method('getIso3Code')->will($this->returnValue('USA'));
        $address->setCountry($country);

        $this->assertEquals('USA', $address->getCountryIso3());
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(array $actualData, $expected)
    {
        $address = $this->createAddress();

        foreach ($actualData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            $address->$setter($value);
        }

        $this->assertTrue(method_exists($address, '__toString'));
        $this->assertEquals($expected, $address->__toString());
    }

    /**
     * @return array
     */
    public function toStringDataProvider()
    {
        return array(
            array(
                array(
                    'firstName' => 'FirstName',
                    'lastName' => 'LastName',
                    'street' => 'Street',
                    'region' => $this->createMockRegion('some region'),
                    'postalCode' => '12345',
                    'country' => $this->createMockCountry('Ukraine'),
                ),
                'FirstName LastName , Street   some region , Ukraine 12345'
            ),
            array(
                array(
                    'firstName' => '',
                    'lastName' => 'LastName',
                    'street' => 'Street',
                    'region' => $this->createMockRegion('some region'),
                    'postalCode' => '',
                    'country' => $this->createMockCountry('Ukraine'),
                ),
                'LastName , Street   some region , Ukraine'
            ),
            array(
                array(
                    'firstName' => '',
                    'lastName' => '',
                    'street' => '',
                    'region' => '',
                    'postalCode' => '',
                    'country' => '',
                ),
                ''
            ),
        );
    }

    /**
     * @param string $name
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockCountry($name)
    {
        $result = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->getMock();

        $result->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($name));

        return $result;
    }

    /**
     * @param string $name
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockRegion($name)
    {
        $result = $this->createMock('Oro\Bundle\AddressBundle\Entity\Region', array(), array('combinedCode'));
        $result->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($name));

        return $result;
    }

    public function testRegionText()
    {
        $address = $this->createAddress();

        /** @var \PHPUnit\Framework\MockObject\MockObject|Region $region */
        $region = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Region')
            ->disableOriginalConstructor()
            ->getMock();
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
     * @param string $property
     * @param mixed $value
    */
    public function testIsNotEmpty($property, $value)
    {
        $address = $this->createAddress();
        call_user_func_array(array($address, 'set' . ucfirst($property)), array($value));
        $this->assertFalse($address->isEmpty());
    }

    /**
     * Data provider with entity properties
     *
     * @return array
     */
    public function emptyCheckPropertiesDataProvider()
    {
        $countryMock = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->getMock();
        $regionMock = $this->createMock('Oro\Bundle\AddressBundle\Entity\Region', array(), array('combinedCode'));
        return array(
            'lastName' => array('lastName', 'last name'),
            'firstName' => array('firstName', 'first_name'),
            'street' => array('street', 'street'),
            'street2' => array('street2', 'street2'),
            'city' => array('city', 'city'),
            'region' => array('region', $regionMock),
            'regionText' => array('regionText', 'test region'),
            'postalCode' => array('postalCode', '12345'),
            'country' => array('country', $countryMock),
        );
    }

    /**
     * @dataProvider isEqualDataProvider
     *
     * @param AbstractAddress $one
     * @param mixed $two
     * @param bool $expectedResult
     */
    public function testIsEqual(AbstractAddress $one, $two, $expectedResult)
    {
        $this->assertEquals($expectedResult, $one->isEqual($two));
    }

    /**
     * @return array
     */
    public function isEqualDataProvider()
    {
        $one = $this->createAddress();

        return array(
            array($one, $one, true),
            array($this->createAddress(100), $this->createAddress(100), true),
            array($this->createAddress(), $this->createAddress(), false),
            array($this->createAddress(100), $this->createAddress(), false),
            array($this->createAddress(), null, false),
        );
    }

    /**
     * @param int|null $id
     * @return AbstractAddress|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createAddress($id = null)
    {
        /** @var AbstractAddress $result */
        $result = $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress');

        if (null !== $id) {
            $result->setId($id);
        }

        return $result;
    }
}
