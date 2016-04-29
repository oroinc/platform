<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractDatagridTestCase extends WebTestCase
{
    /**
     * Should return data for test grid method
     * Format of data is following:
     *   [
     *      'gridParameters' => array of params needed to pass to grid request, required param 'gridName'
     *      'gridFilters'    => array of filters
     *   ]
     *
     * @return array
     */
    abstract public function gridProvider();

    /**
     * @var bool
     */
    protected $isRealGridRequest = true;

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1])
        );
    }

    /**
     * @dataProvider gridProvider
     *
     * @param array $requestData
     */
    public function testGrid($requestData)
    {
        $response = $this->client->requestGrid(
            $requestData['gridParameters'],
            $requestData['gridFilters'],
            $this->isRealGridRequest
        );
        $result = $this->getJsonResponseContent($response, 200);

        foreach ($result['data'] as $row) {
            foreach ($requestData['assert'] as $fieldName => $value) {
                $this->assertEquals($value, $row[$fieldName]);
            }
            break;
        }

        $this->assertCount((int) $requestData['expectedResultCount'], $result['data']);
    }
}
