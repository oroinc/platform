<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class SoapAdvancedSearchApiTest extends WebTestCase
{
    /** Default value for offset and max_records */
    const DEFAULT_VALUE = 0;

    /**
     * @var Client
     */
    protected $client;

    protected static $hasLoaded = false;

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
        $this->client->appendFixturesOnce(__DIR__ . DIRECTORY_SEPARATOR . 'DataFixtures');
    }

    /**
     * @dataProvider advancedSearchDataProvider
     */
    public function testAdvancedSearch(array $request, array $response)
    {
        $result = $this->client->getSoapClient()->advancedSearch($request['query']);
        $result = $this->valueToArray($result);
        $this->assertEquals($response['count'], $result['count']);
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
    public function advancedSearchDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'advanced_requests');
    }
}
