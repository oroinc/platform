<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

/**
 * @group search
 * @dbIsolationPerTest
 */
class SearchControllerTest extends SearchBundleWebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateBasicAuthHeader());

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
     * @dataProvider searchDataProvider
     */
    public function testSearchSuggestion(array $request, array $response)
    {
        $this->addOroDefaultPrefixToUrlInParameterArray($response['rest']['data'], 'record_url');
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

        $actualResponse = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($actualResponse, 200);

        $actualContent = self::jsonToArray($actualResponse->getContent());

        self::assertThat($actualContent['data'], new ArrayContainsConstraint($response['rest']['data'], false));
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'requests');
    }
}
