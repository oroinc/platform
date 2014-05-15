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
        if (isset($response['rest']['data']) && is_array($response['rest']['data'])) {
            foreach ($response['rest']['data'] as $key => $object) {
                foreach ($object as $property => $value) {
                    $this->assertEquals($value, $result['data'][$key][$property]);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'requests');
    }
}
