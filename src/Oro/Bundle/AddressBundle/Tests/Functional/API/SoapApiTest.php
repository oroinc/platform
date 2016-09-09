<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @group soap
 */
class SoapApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
    }

    /**
     * @return array
     */
    public function testGetCountries()
    {
        $result = $this->soapClient->getCountries();
        $result = $this->valueToArray($result);
        return array_slice($result['item'], 0, 5);
    }

    /**
     * @depends testGetCountries
     * @param array $countries
     */
    public function testGetCountry(array $countries)
    {
        foreach ($countries as $country) {
            $result = $this->soapClient->getCountry($country['iso2Code']);
            $result = $this->valueToArray($result);
            $this->assertEquals($country, $result);
        }
    }

    /**
     * @return array
     */
    public function testGetRegions()
    {
        $result = $this->soapClient->getRegions();
        $result = $this->valueToArray($result);
        return array_slice($result['item'], 0, 5);
    }

    /**
     * @depends testGetRegions
     * @param array $regions
     */
    public function testGetRegion(array $regions)
    {
        foreach ($regions as $region) {
            $result = $this->soapClient->getRegion($region['combinedCode']);
            $result = $this->valueToArray($result);
            $this->assertEquals($region, $result);
        }
    }

    /**
     * @depends testGetRegion
     */
    public function testGetCountryRegion()
    {
        $result = $this->soapClient->getRegionByCountry('US');
        $result = $this->valueToArray($result);

        $region = current($result['item']);

        $expectedResult = $this->soapClient->getRegion($region['combinedCode']);
        $expectedResult = $this->valueToArray($expectedResult);
        $this->assertEquals($expectedResult, $region);
    }
}
