<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
 * @group search
 */
class RestAdvancedSearchApiTest extends SearchBundleWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixture(Item::class, LoadSearchItemData::class, LoadSearchItemData::COUNT);
    }

    /**
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
        $result = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

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
        $result = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($response['code'], $result['code']);
        $this->assertEquals($response['message'], $result['message']);
    }

    public function advancedSearchDataProvider(): array
    {
        return $this->getApiRequestsData(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'advanced_requests'
        );
    }

    public function advancedSearchBadRequestDataProvider(): array
    {
        return $this->getApiRequestsData(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'advanced_search_bad_requests'
        );
    }
}
