<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class SoapApiTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
        $this->client->soap(
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
        $result = $this->client->getSoap()->getCountries();
        $result = ToolsAPI::classToArray($result);
        return array_slice($result['item'], 0, 5);
    }

    /**
     * @depends testGetCountries
     * @param $countries
     */
    public function testGetCountry($countries)
    {
        foreach ($countries as $country) {
            $result = $this->client->getSoap()->getCountry($country['iso2Code']);
            $result = ToolsAPI::classToArray($result);
            $this->assertEquals($country, $result);
        }
    }

    /**
     * @return array
     */
    public function testGetRegions()
    {
        $result = $this->client->getSoap()->getRegions();
        $result = ToolsAPI::classToArray($result);
        return array_slice($result['item'], 0, 5);
    }

    /**
     * @depends testGetRegions
     * @param $regions
     */
    public function testGetRegion($regions)
    {
        foreach ($regions as $region) {
            $result = $this->client->getSoap()->getRegion($region['combinedCode']);
            $result = ToolsAPI::classToArray($result);
            $this->assertEquals($region, $result);
        }
    }

    /**
     * @depends testGetRegion
     */
    public function testGetCountryRegion()
    {
        $result = $this->client->getSoap()->getRegionByCountry('US');
        $result = ToolsAPI::classToArray($result);

        $region = current($result['item']);

        $expectedResult = $this->client->getSoap()->getRegion($region['combinedCode']);
        $expectedResult = ToolsAPI::classToArray($expectedResult);
        $this->assertEquals($expectedResult, $region);
    }
}
