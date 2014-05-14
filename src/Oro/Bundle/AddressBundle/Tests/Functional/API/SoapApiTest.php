<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SoapApiTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = self::createClient(array(), $this->generateWsseAuthHeader());
        $this->client->createSoapClient(
            "http://localhost/api/soap",
            array(
                'location' => 'http://localhost/api/soap',
                'soap_version' => SOAP_1_2
            )
        );
    }

    /**
     * @return array
     */
    public function testGetCountries()
    {
        $result = $this->client->getSoapClient()->getCountries();
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
            $result = $this->client->getSoapClient()->getCountry($country['iso2Code']);
            $result = $this->valueToArray($result);
            $this->assertEquals($country, $result);
        }
    }

    /**
     * @return array
     */
    public function testGetRegions()
    {
        $result = $this->client->getSoapClient()->getRegions();
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
            $result = $this->client->getSoapClient()->getRegion($region['combinedCode']);
            $result = $this->valueToArray($result);
            $this->assertEquals($region, $result);
        }
    }

    /**
     * @depends testGetRegion
     */
    public function testGetCountryRegion()
    {
        $result = $this->client->getSoapClient()->getRegionByCountry('US');
        $result = $this->valueToArray($result);

        $region = current($result['item']);

        $expectedResult = $this->client->getSoapClient()->getRegion($region['combinedCode']);
        $expectedResult = $this->valueToArray($expectedResult);
        $this->assertEquals($expectedResult, $region);
    }
}
