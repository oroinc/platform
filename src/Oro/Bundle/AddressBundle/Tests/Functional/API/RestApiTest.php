<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RestApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    /**
     * @return array
     */
    public function testGetCountries()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_countries')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return array_slice($result, 0, 5);
    }

    /**
     * @depends testGetCountries
     * @param $countries
     */
    public function testGetCountry($countries)
    {
        foreach ($countries as $country) {
            $this->client->request(
                'GET',
                $this->getUrl('oro_api_get_country', array('id' => $country['iso2code']))
            );

            $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

            $this->assertEquals($country, $result);
        }
    }

    public function testGetRegion()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_region'),
            array('id' => 'US-LA')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals('US-LA', $result['combinedCode']);
    }

    public function testGetCountryRegions()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_country_get_regions', array('country' => 'US'))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        foreach ($result as $region) {
            $this->client->request(
                'GET',
                $this->getUrl('oro_api_get_region'),
                array('id' => $region['combinedCode']),
                array(),
                $this->generateWsseAuthHeader()
            );

            $expectedResult = $this->getJsonResponseContent($this->client->getResponse(), 200);

            $this->assertEquals($expectedResult, $region);
        }
    }
}
