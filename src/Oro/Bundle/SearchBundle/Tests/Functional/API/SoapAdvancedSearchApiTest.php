<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\API;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class SoapAdvancedSearchApiTest extends WebTestCase
{
    /** Default value for offset and max_records */
    const DEFAULT_VALUE = 0;

    protected static $hasLoaded = false;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
        $this->loadFixtures(array('Oro\Bundle\SearchBundle\Tests\Functional\API\DataFixtures\LoadSearchItemData'));
    }

    /**
     * @dataProvider advancedSearchDataProvider
     */
    public function testAdvancedSearch(array $request, array $response)
    {
        $result = $this->soapClient->advancedSearch($request['query']);
        $result = $this->valueToArray($result);
        $this->assertEquals($response['records_count'], $result['recordsCount']);
        $this->assertEquals($response['count'], $result['count']);

        // if only one element
        if (empty($result['elements']['item'][0])) {
            $result['elements']['item'] = array($result['elements']['item']);
        }

        // remove ID references
        foreach (array_keys($result['elements']['item']) as $key) {
            unset($result['elements']['item'][$key]['recordId']);
        }

        $this->assertSame($response['soap']['data'], $result['elements']['item']);
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
    public function advancedSearchDataProvider()
    {
        return $this->getApiRequestsData(__DIR__ . DIRECTORY_SEPARATOR . 'advanced_requests');
    }
}
