<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchProductData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;

/**
 * @group search
 * @dbIsolationPerTest
 */
class RestSearchApiTest extends SearchBundleWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixture(Item::class, LoadSearchItemData::class, LoadSearchItemData::COUNT);
        $this->loadFixture(Product::class, LoadSearchProductData::class, count(LoadSearchProductData::PRODUCTS));
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $request, array $response)
    {
        $this->addOroDefaultPrefixToUrlInParameterArray($response['rest']['data'], 'record_url');
        if (array_key_exists('supported_engines', $request)) {
            $engine = $this->getContainer()
                ->get('oro_search.engine.parameters')
                ->getEngineName();
            if (!in_array($engine, $request['supported_engines'], true)) {
                $this->markTestIncomplete(sprintf('Test should not be executed on "%s" engine', $engine));
            }
            unset($request['supported_engines']);
        }

        $request = array_filter($request);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_search'),
            $request
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($response['records_count'], $result['records_count']);
        $this->assertEquals($response['count'], $result['count']);

        // remove ID references and data
        foreach (array_keys($result['data']) as $key) {
            unset($result['data'][$key]['record_id'], $result['data'][$key]['selected_data']);
        }

        $this->assertResultHasItems($response['rest']['data'], $result['data']);
    }

    public function searchDataProvider(): array
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'requests');
    }

    private function assertResultHasItems(array $items, array $result): void
    {
        foreach ($items as $item) {
            $this->assertContains($item, $result);
        }
    }
}
