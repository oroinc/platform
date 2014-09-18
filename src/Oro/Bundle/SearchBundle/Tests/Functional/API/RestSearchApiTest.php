<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class RestSearchApiTest extends WebTestCase
{
    protected static $hasLoaded = false;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(array('Oro\Bundle\SearchBundle\Tests\Functional\API\DataFixtures\LoadSearchItemData'));
    }

    /**
     * @param array $request
     * @param array $response
     *
     * @dataProvider searchDataProvider
     */
    public function testSearch(array $request, array $response)
    {
        if (array_key_exists('supported_engines', $request)) {
            $engine = $this->getContainer()->getParameter('oro_search.engine');
            if (!in_array($engine, $request['supported_engines'])) {
                $this->markTestIncomplete('Test should not be executed on this engine');
            }
            unset($request['supported_engines']);
        }

        foreach ($request as $key => $value) {
            if (is_null($value)) {
                unset($request[$key]);
            }
        }

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_search'),
            $request
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $result = json_decode($result->getContent(), true);

        $this->assertEquals($response['records_count'], $result['records_count']);
        $this->assertEquals($response['count'], $result['count']);

        if (empty($result['data'])) {
            $result['data'] = [];
        }

        // remove ID references
        $recordsRequired = !empty($response['rest']['data'][0]['record_string']);
        foreach (array_keys($result['data']) as $key) {
            unset($result['data'][$key]['record_id']);
            if (!$recordsRequired) {
                unset($result['data'][$key]['record_string']);
            }
        }

        $this->assertResultHasItems($response['rest']['data'], $result['data']);
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'requests');
    }

    /**
     * @param array $items
     * @param array $result
     */
    protected function assertResultHasItems(array $items, array $result)
    {
        foreach ($items as $item) {
            $this->assertContains($item, $result);
        }
    }
}
