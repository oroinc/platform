<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestApiTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
    }

    public function testGetCountries(): array
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_countries')
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        return array_slice($result, 0, 5);
    }

    /**
     * @depends testGetCountries
     */
    public function testGetCountry($countries)
    {
        foreach ($countries as $country) {
            $this->client->jsonRequest(
                'GET',
                $this->getUrl('oro_api_get_country', ['id' => $country['iso2code']])
            );

            $result = self::getJsonResponseContent($this->client->getResponse(), 200);

            $this->assertEquals($country, $result);
        }

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_country', ['id' => 'US'])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'iso2code' => 'US',
                'iso3code' => 'USA',
                'name'     => 'United States'
            ],
            $result
        );
    }

    public function testGetRegion()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_region', ['id' => 'US-LA'])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'combinedCode' => 'US-LA',
                'code'         => 'LA',
                'name'         => 'Louisiana',
                'country'      => 'US'
            ],
            $result
        );
    }

    public function testGetCountryRegions()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_country_get_regions', ['country' => 'US'])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);
        $result = array_slice($result, 0, 5);

        foreach ($result as $region) {
            $this->client->jsonRequest(
                'GET',
                $this->getUrl('oro_api_get_region', ['id' => $region['combinedCode']]),
                [],
                self::generateWsseAuthHeader()
            );

            $expectedResult = self::getJsonResponseContent($this->client->getResponse(), 200);

            $this->assertEquals($expectedResult, $region);
        }
    }
}
