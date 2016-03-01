<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @dbReindex
 */
class SearchControllerTest extends WebTestCase
{
    /**
     * @var bool
     */
    protected static $hasLoaded = false;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData']);
    }

    /**
     * @param array $request
     * @param array $response
     *
     * @dataProvider searchDataProvider
     */
    public function testSearchSuggestion(array $request, array $response)
    {
        if (array_key_exists('supported_engines', $request)) {
            $engine = $this->getContainer()->getParameter('oro_search.engine');
            if (!in_array($engine, $request['supported_engines'])) {
                $this->markTestIncomplete('Test should not be executed on this engine');
            }
            unset($request['supported_engines']);
        }

        $request = array_filter($request);

        $this->client->request(
            'GET',
            $this->getUrl('oro_search_suggestion'),
            $request
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();

        foreach ($response['rest']['data'] as $item) {
            $this->assertContains($item['record_url'], $content);
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
