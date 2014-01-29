<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\ToolsAPI;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\BrowserKit\Response;

/**
 * @outputBuffering enabled
 * @db_isolation
 */
class RestApiTest extends WebTestCase
{
    /** @var Client */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient(array(), ToolsAPI::generateWsseHeader());
    }

    /**
     * @return array
     */
    public function testGetCountries()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_countries')
        );

        /** @var $result Response */
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
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
                $this->client->generate('oro_api_get_country', array('id' => $country['iso2code']))
            );
            /** @var $result Response */
            $result = $this->client->getResponse();
            ToolsAPI::assertJsonResponse($result, 200);
            $result = ToolsAPI::jsonToArray($result->getContent());
            $this->assertEquals($country, $result);
        }
    }

    public function testGetRegion()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_get_region'),
            array('id' => 'US.LA')
        );
        /** @var $result Response */
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        $this->assertEquals('US.LA', $result['combinedCode']);
    }

    public function testGetCountryRegions()
    {
        $this->client->request(
            'GET',
            $this->client->generate('oro_api_country_get_regions', array('country' => 'US'))
        );
        /** @var $result Response */
        $result = $this->client->getResponse();
        ToolsAPI::assertJsonResponse($result, 200);
        $result = ToolsAPI::jsonToArray($result->getContent());
        foreach ($result as $region) {
            $this->client->request(
                'GET',
                $this->client->generate('oro_api_get_region'),
                array('id' => $region['combinedCode'])
            );
            /** @var $result Response */
            $expectedResult = $this->client->getResponse();
            ToolsAPI::assertJsonResponse($expectedResult, 200);
            $expectedResult = ToolsAPI::jsonToArray($expectedResult->getContent());
            $this->assertEquals($expectedResult, $region);
        }
    }
}
