<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @dbReindex
 */
class RestAdvancedSearchApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData']);
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
            ['query' => $requestUrl]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);

        //compare result
        $this->assertEquals($response['records_count'], $result['records_count']);
        $this->assertEquals($response['count'], $result['count']);
        $this->assertSameSize($response['rest']['data'], $result['data']);

        // remove ID references
        foreach (array_keys($result['data']) as $key) {
            unset($result['data'][$key]['record_id']);
        }

        $this->assertSame($response['rest']['data'], $result['data']);
    }

    /**
     * @return array
     */
    public function advancedSearchDataProvider()
    {
        return $this->getApiRequestsData(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'advanced_requests'
        );
    }
}
