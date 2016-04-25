<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;

class RestFilterValueAccessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param Request $request
     * @param array   $queryValues
     * @param bool    $isEmptyRequest
     *
     * @dataProvider requestProvider
     */
    public function testAccessor(Request $request, $queryValues = [], $isEmptyRequest = false)
    {
        $accessor = new RestFilterValueAccessor($request);

        foreach ($queryValues as $itemKey => $itemValue) {
            list($key, $operator, $value) = $itemValue;
            $path = isset($itemValue[3]) ? $itemValue[3] : $key;
            $this->assertTrue($accessor->has($key), sprintf('has - %s', $itemKey));
            $filterValue = $accessor->get($key);
            $this->assertEquals($path, $filterValue->getPath(), sprintf('path - %s', $itemKey));
            $this->assertEquals($value, $filterValue->getValue(), sprintf('value - %s', $itemKey));
            $this->assertEquals($operator, $filterValue->getOperator(), sprintf('operator - %s', $itemKey));
        }

        $this->assertFalse($accessor->has('unknown'), 'has - unknown');
        $this->assertNull($accessor->get('unknown'), 'get - unknown');

        // test getAll
        $this->assertCount(count($queryValues), $accessor->getAll(), 'getAll');

        if ($isEmptyRequest) {
            // empty request without filters, no additional asserts required
            return;
        }

        // test getAll for a group
        $filterValues = $accessor->getAll('prm1');
        $this->assertCount(1, $filterValues, 'getAll(prm1)');
        $this->assertEquals('val1', $filterValues['prm1']->getValue(), 'value - getAll(prm1)');
        $filterValues = $accessor->getAll('filter');
        $this->assertCount(4, $filterValues, 'getAll(filter)');
        $this->assertEquals(
            'US',
            $filterValues['filter[address.country]']->getValue(),
            'value - getAll(filter)[address.country]'
        );
        $this->assertEquals(
            'NY',
            $filterValues['filter[address.region]']->getValue(),
            'value - getAll(filter)[address.region]'
        );
        $this->assertEquals(
            'billing',
            $filterValues['filter[address][type]']->getValue(),
            'value - getAll(filter)[address][type]'
        );
        $this->assertEquals(
            'Z123',
            $filterValues['filter[address][code]']->getValue(),
            'value - getAll(filter)[address][code]'
        );
    }

    public function requestProvider()
    {
        $queryStringValues = [
            'prm1=val1'                          => ['prm1', '=', 'val1'],
            'prm2<>val2'                         => ['prm2', '<>', 'val2'],
            'prm3<val3'                          => ['prm3', '<', 'val3'],
            'prm4<=val4'                         => ['prm4', '<=', 'val4'],
            'prm5>val5'                          => ['prm5', '>', 'val5'],
            'prm6>=val6'                         => ['prm6', '>=', 'val6'],
            'prm7%3C%3Eval7'                     => ['prm7', '<>', 'val7'],
            'prm8%3Cval8'                        => ['prm8', '<', 'val8'],
            'prm9%3C=val9'                       => ['prm9', '<=', 'val9'],
            'prm10%3Eval10'                      => ['prm10', '>', 'val10'],
            'prm11%3E=val11'                     => ['prm11', '>=', 'val11'],
            'prm12<><val12>'                     => ['prm12', '<>', '<val12>'],
            'prm_13=%3Cval13%3E'                 => ['prm_13', '=', '<val13>'],
            'page[number]=123'                   => ['page[number]', '=', '123', 'number'],
            'page%5Bsize%5D=456'                 => ['page[size]', '=', '456', 'size'],
            'filter[address.country]=US'         => ['filter[address.country]', '=', 'US', 'address.country'],
            'filter%5Baddress.region%5D=NY'      => ['filter[address.region]', '=', 'NY', 'address.region'],
            'filter[address][type]=billing'      => ['filter[address][type]', '=', 'billing', 'address.type'],
            'filter%5Baddress%5D%5Bcode%5D=Z123' => ['filter[address][code]', '=', 'Z123', 'address.code'],
        ];

        return [
            'testWithQueryString' => [
                'request' => Request::create(
                    'http://test.com?' . implode('&', array_keys($queryStringValues))
                ),
                'queryValues' => $queryStringValues
            ],
            'testWithContent'     => [
                'request' => Request::create(
                    'http://test.com',
                    'GET',
                    [],
                    [],
                    [],
                    [],
                    implode('&', array_keys($queryStringValues))
                ),
                'queryValues' => $queryStringValues
            ],
            'testWithQueryStringAndContent' => [
                'request' => Request::create(
                    'http://test.com?' . implode('&', array_chunk(array_keys($queryStringValues), 10)[0]),
                    'GET',
                    [],
                    [],
                    [],
                    [],
                    implode('&', array_chunk(array_keys($queryStringValues), 10)[1])
                ),
                'queryValues' => $queryStringValues,
            ],
            'testEmpty' => [
                'request' => Request::create('http://test.com'),
                'queryValues' => [],
                'isEmptyValues' => true,
            ]
        ];
    }

    public function testOverrideExistingFilterValue()
    {
        $accessor = new RestFilterValueAccessor(Request::create('http://test.com?prm1=val1'));

        $this->assertEquals(new FilterValue('prm1', 'val1', '='), $accessor->get('prm1'));

        // test override existing filter value
        $accessor->set('prm1', new FilterValue('prm1', 'val11', '='));
        $this->assertEquals(new FilterValue('prm1', 'val11', '='), $accessor->get('prm1'));
    }

    public function testAddNewFilterValue()
    {
        $accessor = new RestFilterValueAccessor(Request::create('http://test.com'));

        $this->assertNull($accessor->get('prm1'));

        $accessor->set('prm1', new FilterValue('prm1', 'val11', '='));
        $this->assertEquals(new FilterValue('prm1', 'val11', '='), $accessor->get('prm1'));
    }

    public function testRemoveExistingFilterValue()
    {
        $accessor = new RestFilterValueAccessor(Request::create('http://test.com?prm1=val1'));

        $this->assertEquals(new FilterValue('prm1', 'val1', '='), $accessor->get('prm1'));

        // test override existing filter value
        $accessor->set('prm1');
        $this->assertNull($accessor->get('prm1'));

        $this->assertCount(0, $accessor->getAll());
    }
}
