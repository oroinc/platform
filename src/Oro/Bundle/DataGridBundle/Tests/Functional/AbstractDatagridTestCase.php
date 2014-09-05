<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractDatagridTestCase extends WebTestCase
{
    abstract public function gridProvider();

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
     * @param array $filters
     */
    public function testGrid($filters)
    {
        $this->client->requestGrid($filters['gridParameters'], $filters['gridFilters']);
        $response = $this->client->getResponse();
        $result   = $this->getJsonResponseContent($response, 200);

        foreach ($result['data'] as $row) {
            foreach ($filters['assert'] as $fieldName => $value) {
                $this->assertEquals($value, $row[$fieldName]);
            }
            break;
        }

        $this->assertCount((int) $filters['expectedResultCount'], $result['data']);
    }
}

