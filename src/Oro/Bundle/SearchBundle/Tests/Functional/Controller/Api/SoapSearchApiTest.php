<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @group soap
 * @group search
 */
class SoapSearchApiTest extends WebTestCase
{
    use SearchExtensionTrait;

    /** Default value for offset and max_records */
    const DEFAULT_VALUE = 0;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->initSoapClient();
        $this->startTransaction();

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->ensureItemsLoaded($alias, 0);

        $this->loadFixtures([LoadSearchItemData::class], true);
        $this->getSearchIndexer()->reindex(Item::class);
        $this->ensureItemsLoaded($alias, LoadSearchItemData::COUNT);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
    }

    /**
     * @param array $request
     * @param array $response
     * @SuppressWarnings(PHPMD.NPathComplexity)
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

        if (is_null($request['search'])) {
            $request['search'] ='';
        }
        if (is_null($request['offset'])) {
            $request['offset'] = self::DEFAULT_VALUE;
        }
        if (is_null($request['max_results'])) {
            $request['max_results'] = self::DEFAULT_VALUE;
        }

        $result = $this->soapClient->search(
            $request['search'],
            $request['offset'],
            $request['max_results']
        );
        $result = $this->valueToArray($result);

        $this->assertEquals($response['records_count'], $result['recordsCount']);
        $this->assertEquals($response['count'], $result['count']);

        if (empty($result['elements']['item'])) {
            $result['elements']['item'] = [];
        }

        // if only one element
        if (empty($result['elements']['item'][0])) {
            $result['elements']['item'] = [$result['elements']['item']];
        }

        // remove ID references
        $recordsRequired = !empty($response['soap']['item'][0]['recordTitle']);
        foreach (array_keys($result['elements']['item']) as $key) {
            unset($result['elements']['item'][$key]['recordId']);
            if (!$recordsRequired) {
                unset($result['elements']['item'][$key]['recordTitle']);
            }
        }

        $this->addOroDefaultPrefixToUrlInParameterArray($response['soap']['item'], 'recordUrl');
        $this->assertResultHasItems($response['soap']['item'], $result['elements']['item']);
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
    public function searchDataProvider()
    {
        return $this->getApiRequestsData(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'requests'
        );
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
