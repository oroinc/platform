<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
 * @dbIsolationPerTest
 * @group search
 */
class RestAdvancedSearchApiTest extends SearchBundleWebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader());

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->ensureItemsLoaded($alias, 0);

        $this->loadFixtures([LoadSearchItemData::class]);
        $this->getSearchIndexer()->reindex(Item::class);
        $this->ensureItemsLoaded($alias, LoadSearchItemData::COUNT);
    }

    /**
     * @param array $request
     * @param array $response
     *
     * @dataProvider advancedSearchDataProvider
     */
    public function testAdvancedSearch(array $request, array $response)
    {
        $this->addOroDefaultPrefixToUrlInParameterArray($response['rest']['data'], 'record_url');
        $queryString = $request['query'];
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_search_advanced'),
            ['query' => $queryString]
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
     * @param array $request
     * @param array $response
     *
     * @dataProvider advancedSearchBadRequestDataProvider
     */
    public function testAdvancedSearchBadRequest(array $request, array $response)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_search_advanced'),
            ['query' => $request['query']]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 400);
        $result = json_decode($result->getContent(), true);
        $this->assertEquals($response['code'], $result['code']);
        $this->assertEquals($response['message'], $result['message']);
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

    /**
     * @return array
     */
    public function advancedSearchBadRequestDataProvider()
    {
        return $this->getApiRequestsData(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'advanced_search_bad_requests'
        );
    }
}
