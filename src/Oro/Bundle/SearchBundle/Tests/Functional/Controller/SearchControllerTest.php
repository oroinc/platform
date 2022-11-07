<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchProductData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;

/**
 * @group search
 */
class SearchControllerTest extends SearchBundleWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixture(Item::class, LoadSearchItemData::class, LoadSearchItemData::COUNT);
        $this->loadFixture(Product::class, LoadSearchProductData::class, count(LoadSearchProductData::PRODUCTS));
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearchSuggestion(array $request, array $response)
    {
        $this->addOroDefaultPrefixToUrlInParameterArray($response['rest']['data'], 'record_url');
        if (array_key_exists('supported_engines', $request)) {
            $engine = $this->getContainer()
                ->get('oro_search.engine.parameters')
                ->getEngineName();
            if (!in_array($engine, $request['supported_engines'], true)) {
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

    public function searchDataProvider(): array
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'requests');
    }
}
