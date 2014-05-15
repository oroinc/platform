<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class RestAdvancedSearchApiTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(array('Oro\Bundle\SearchBundle\Tests\Functional\API\DataFixtures\LoadSearchItemData'));
    }

    /**
     * @param array $request
     * @param array $response
     *
     * @dataProvider advancedSearchDataProvider
     */
    public function testAdvancedSearch(array $request, array $response)
    {
        $requestUrl = $request['query'];
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_search_advanced'),
            array('query' => $requestUrl)
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);

        //compare result
        $this->assertEquals($response['records_count'], $result['records_count']);
        $this->assertEquals($response['count'], $result['count']);
    }

    /**
     * @return array
     */
    public function advancedSearchDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'advanced_requests');
    }
}
