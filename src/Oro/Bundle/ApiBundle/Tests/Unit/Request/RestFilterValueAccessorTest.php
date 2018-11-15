<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RestFilterValueAccessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param Request $request
     *
     * @return RestFilterValueAccessor
     */
    private function getRestFilterValueAccessor(Request $request)
    {
        return new RestFilterValueAccessor(
            $request,
            '(!|<|>|%21|%3C|%3E)?(=|%3D)|<>|%3C%3E|<|>|\*|%3C|%3E|%2A|(!|%21)?(\*|~|\^|\$|%2A|%7E|%5E|%24)',
            [
                ComparisonFilter::EQ              => '=',
                ComparisonFilter::NEQ             => '!=',
                ComparisonFilter::GT              => '>',
                ComparisonFilter::LT              => '<',
                ComparisonFilter::GTE             => '>=',
                ComparisonFilter::LTE             => '<=',
                ComparisonFilter::EXISTS          => '*',
                ComparisonFilter::NEQ_OR_NULL     => '!*',
                ComparisonFilter::CONTAINS        => '~',
                ComparisonFilter::NOT_CONTAINS    => '!~',
                ComparisonFilter::STARTS_WITH     => '^',
                ComparisonFilter::NOT_STARTS_WITH => '!^',
                ComparisonFilter::ENDS_WITH       => '$',
                ComparisonFilter::NOT_ENDS_WITH   => '!$'
            ]
        );
    }

    /**
     * @param string $path
     * @param mixed  $value
     * @param string $operator
     * @param string $sourceKey
     *
     * @return FilterValue
     */
    private function getFilterValue($path, $value, $operator, $sourceKey)
    {
        return FilterValue::createFromSource($sourceKey, $path, $value, $operator);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testParseQueryString()
    {
        $queryStringValues = [
            'prm1=val1'                          => ['prm1', 'eq', 'val1', 'prm1', 'prm1'],
            'PRM1=val1'                          => ['PRM1', 'eq', 'val1', 'PRM1', 'PRM1'],
            'prm2<>val2'                         => ['prm2', 'neq', 'val2', 'prm2', 'prm2'],
            'prm3<val3'                          => ['prm3', 'lt', 'val3', 'prm3', 'prm3'],
            'prm4<=val4'                         => ['prm4', 'lte', 'val4', 'prm4', 'prm4'],
            'prm5>val5'                          => ['prm5', 'gt', 'val5', 'prm5', 'prm5'],
            'prm6>=val6'                         => ['prm6', 'gte', 'val6', 'prm6', 'prm6'],
            'prm7%3C%3Eval7'                     => ['prm7', 'neq', 'val7', 'prm7', 'prm7'],
            'PRM7%3C%3Eval7'                     => ['PRM7', 'neq', 'val7', 'PRM7', 'PRM7'],
            'prm8%3Cval8'                        => ['prm8', 'lt', 'val8', 'prm8', 'prm8'],
            'prm9%3C=val9'                       => ['prm9', 'lte', 'val9', 'prm9', 'prm9'],
            'prm10%3Eval10'                      => ['prm10', 'gt', 'val10', 'prm10', 'prm10'],
            'prm11%3E=val11'                     => ['prm11', 'gte', 'val11', 'prm11', 'prm11'],
            'prm12<><val12>'                     => ['prm12', 'neq', '<val12>', 'prm12', 'prm12'],
            'prm_13=%3Cval13%3E'                 => ['prm_13', 'eq', '<val13>', 'prm_13', 'prm_13'],
            'prm14!=val14'                       => ['prm14', 'neq', 'val14', 'prm14', 'prm14'],
            'prm15%21=val15'                     => ['prm15', 'neq', 'val15', 'prm15', 'prm15'],
            'prm16%3Dval16'                      => ['prm16', 'eq', 'val16', 'prm16', 'prm16'],
            'prm17=val%26%3F'                    => ['prm17', 'eq', 'val&?', 'prm17', 'prm17'],
            'page[number]=123'                   => ['page[number]', 'eq', '123', 'number', 'page[number]'],
            'page%5Bsize%5D=456'                 => ['page[size]', 'eq', '456', 'size', 'page[size]'],
            'filter[address.country]=US'         => [
                'filter[address.country]',
                'eq',
                'US',
                'address.country',
                'filter[address.country]'
            ],
            'filter[address.defaultRegion]=LA'   => [
                'filter[address.defaultRegion]',
                'eq',
                'LA',
                'address.defaultRegion',
                'filter[address.defaultRegion]'
            ],
            'filter%5Baddress.region%5D=NY'      => [
                'filter[address.region]',
                'eq',
                'NY',
                'address.region',
                'filter[address.region]'
            ],
            'filter[address][type]=billing'      => [
                'filter[address.type]',
                'eq',
                'billing',
                'address.type',
                'filter[address][type]'
            ],
            'filter%5Baddress%5D%5Bcode%5D=Z123' => [
                'filter[address.code]',
                'eq',
                'Z123',
                'address.code',
                'filter[address][code]'
            ],
            'filter%5Baddress%5D%5Bname%5D%3Da1' => [
                'filter[address.name]',
                'eq',
                'a1',
                'address.name',
                'filter[address][name]'
            ],
            'empty[prm20][]=123'                 => ['empty[prm20.]', 'eq', '123', 'prm20.', 'empty[prm20][]'],
            'empty%5Bprm21%5D%5B%5D=123'         => ['empty[prm21.]', 'eq', '123', 'prm21.', 'empty[prm21][]'],
            'empty[prm22][][]=123'               => ['empty[prm22..]', 'eq', '123', 'prm22..', 'empty[prm22][][]'],
            'empty%5Bprm23%5D%5B%5D%5B%5D=123'   => ['empty[prm23..]', 'eq', '123', 'prm23..', 'empty[prm23][][]']
        ];
        $request = Request::create(
            'http://test.com?' . implode('&', array_keys($queryStringValues))
        );

        $accessor = $this->getRestFilterValueAccessor($request);

        $expectedQueryString =
            'PRM1=val1'
            . '&PRM7%5Bneq%5D=val7'
            . '&empty%5Bprm20%5D%5B%5D=123'
            . '&empty%5Bprm21%5D%5B%5D=123'
            . '&empty%5Bprm22%5D%5B%5D%5B%5D=123'
            . '&empty%5Bprm23%5D%5B%5D%5B%5D=123'
            . '&filter%5Baddress%5D%5Bcountry%5D=US'
            . '&filter%5Baddress%5D%5BdefaultRegion%5D=LA'
            . '&filter%5Baddress%5D%5Bregion%5D=NY'
            . '&filter%5Baddress%5D%5Bcode%5D=Z123'
            . '&filter%5Baddress%5D%5Bname%5D=a1'
            . '&filter%5Baddress%5D%5Btype%5D=billing'
            . '&page%5Bnumber%5D=123'
            . '&page%5Bsize%5D=456'
            . '&prm1=val1'
            . '&prm10%5Bgt%5D=val10'
            . '&prm11%5Bgte%5D=val11'
            . '&prm12%5Bneq%5D=%3Cval12%3E'
            . '&prm14%5Bneq%5D=val14'
            . '&prm15%5Bneq%5D=val15'
            . '&prm16=val16'
            . '&prm17=val%26%3F'
            . '&prm2%5Bneq%5D=val2'
            . '&prm3%5Blt%5D=val3'
            . '&prm4%5Blte%5D=val4'
            . '&prm5%5Bgt%5D=val5'
            . '&prm6%5Bgte%5D=val6'
            . '&prm7%5Bneq%5D=val7'
            . '&prm8%5Blt%5D=val8'
            . '&prm9%5Blte%5D=val9'
            . '&prm_13=%3Cval13%3E';
        self::assertEquals(
            $expectedQueryString,
            $accessor->getQueryString()
        );
        // test that built query string can by parsed and stays the same after that
        $anotherQueryString = $this
            ->getRestFilterValueAccessor(Request::create('http://test.com?' . $expectedQueryString))
            ->getQueryString();
        $expectedQueryStringParts = explode('&', $expectedQueryString);
        $anotherQueryStringParts = explode('&', $anotherQueryString);
        sort($expectedQueryStringParts);
        sort($anotherQueryStringParts);
        $expectedQueryString = implode('&', $expectedQueryStringParts);
        $anotherQueryString = implode('&', $anotherQueryStringParts);
        self::assertEquals($expectedQueryString, $anotherQueryString);

        foreach ($queryStringValues as $itemKey => $itemValue) {
            list($key, $operator, $value, $path, $sourceKey) = $itemValue;
            self::assertTrue($accessor->has($key), sprintf('has - %s', $itemKey));
            self::assertEquals(
                $this->getFilterValue($path, $value, $operator, $sourceKey),
                $accessor->get($key),
                $itemKey
            );
        }

        self::assertFalse($accessor->has('unknown'), 'has - unknown');
        self::assertNull($accessor->get('unknown'), 'get - unknown');

        self::assertCount(count($queryStringValues), $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => $this->getFilterValue('prm1', 'val1', 'eq', 'prm1')
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'page[number]' => $this->getFilterValue('number', '123', 'eq', 'page[number]'),
                'page[size]'   => $this->getFilterValue('size', '456', 'eq', 'page[size]')
            ],
            $accessor->getGroup('page')
        );
        self::assertEquals(
            [
                'filter[address.country]'       => $this->getFilterValue(
                    'address.country',
                    'US',
                    'eq',
                    'filter[address.country]'
                ),
                'filter[address.defaultRegion]' => $this->getFilterValue(
                    'address.defaultRegion',
                    'LA',
                    'eq',
                    'filter[address.defaultRegion]'
                ),
                'filter[address.region]'        => $this->getFilterValue(
                    'address.region',
                    'NY',
                    'eq',
                    'filter[address.region]'
                ),
                'filter[address.type]'          => $this->getFilterValue(
                    'address.type',
                    'billing',
                    'eq',
                    'filter[address][type]'
                ),
                'filter[address.code]'          => $this->getFilterValue(
                    'address.code',
                    'Z123',
                    'eq',
                    'filter[address][code]'
                ),
                'filter[address.name]'          => $this->getFilterValue(
                    'address.name',
                    'a1',
                    'eq',
                    'filter[address][name]'
                )
            ],
            $accessor->getGroup('filter')
        );
    }

    public function testParseQueryStringWithAlternativeSyntaxOfFilters()
    {
        $queryStringValues = [
            'prm1[eq]=val1'            => ['prm1', 'eq', 'val1', 'prm1', 'prm1'],
            'prm2[neq]=val2'           => ['prm2', 'neq', 'val2', 'prm2', 'prm2'],
            'prm3[lt]=val3'            => ['prm3', 'lt', 'val3', 'prm3', 'prm3'],
            'prm4[lte]=val4'           => ['prm4', 'lte', 'val4', 'prm4', 'prm4'],
            'prm5[gt]=val5'            => ['prm5', 'gt', 'val5', 'prm5', 'prm5'],
            'prm6[gte]=val6'           => ['prm6', 'gte', 'val6', 'prm6', 'prm6'],
            'filter[field1][eq]=val1'  => ['filter[field1]', 'eq', 'val1', 'field1', 'filter[field1]'],
            'filter[field2][neq]=val2' => ['filter[field2]', 'neq', 'val2', 'field2', 'filter[field2]'],
            'filter[field3][lt]=val3'  => ['filter[field3]', 'lt', 'val3', 'field3', 'filter[field3]'],
            'filter[field4][lte]=val4' => ['filter[field4]', 'lte', 'val4', 'field4', 'filter[field4]'],
            'filter[field5][gt]=val5'  => ['filter[field5]', 'gt', 'val5', 'field5', 'filter[field5]'],
            'filter[field6][gte]=val6' => ['filter[field6]', 'gte', 'val6', 'field6', 'filter[field6]']
        ];
        $request = Request::create(
            'http://test.com?' . implode('&', array_keys($queryStringValues))
        );

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'filter%5Bfield1%5D=val1'
            . '&filter%5Bfield2%5D%5Bneq%5D=val2'
            . '&filter%5Bfield3%5D%5Blt%5D=val3'
            . '&filter%5Bfield4%5D%5Blte%5D=val4'
            . '&filter%5Bfield5%5D%5Bgt%5D=val5'
            . '&filter%5Bfield6%5D%5Bgte%5D=val6'
            . '&prm1=val1'
            . '&prm2%5Bneq%5D=val2'
            . '&prm3%5Blt%5D=val3'
            . '&prm4%5Blte%5D=val4'
            . '&prm5%5Bgt%5D=val5'
            . '&prm6%5Bgte%5D=val6',
            $accessor->getQueryString()
        );

        foreach ($queryStringValues as $itemKey => $itemValue) {
            list($key, $operator, $value, $path, $sourceKey) = $itemValue;
            self::assertTrue($accessor->has($key), sprintf('has - %s', $itemKey));
            self::assertEquals(
                $this->getFilterValue($path, $value, $operator, $sourceKey),
                $accessor->get($key),
                $itemKey
            );
        }

        self::assertFalse($accessor->has('unknown'), 'has - unknown');
        self::assertNull($accessor->get('unknown'), 'get - unknown');

        self::assertCount(count($queryStringValues), $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => $this->getFilterValue('prm1', 'val1', 'eq', 'prm1')
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'filter[field1]' => $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1]'),
                'filter[field2]' => $this->getFilterValue('field2', 'val2', 'neq', 'filter[field2]'),
                'filter[field3]' => $this->getFilterValue('field3', 'val3', 'lt', 'filter[field3]'),
                'filter[field4]' => $this->getFilterValue('field4', 'val4', 'lte', 'filter[field4]'),
                'filter[field5]' => $this->getFilterValue('field5', 'val5', 'gt', 'filter[field5]'),
                'filter[field6]' => $this->getFilterValue('field6', 'val6', 'gte', 'filter[field6]')
            ],
            $accessor->getGroup('filter')
        );
    }

    public function testParseUrlEncodedQueryStringWithAlternativeSyntaxOfFilters()
    {
        $queryStringValues = [
            'filter%5Bfield1%5D%5Beq%5D%3Dval1' => ['filter[field1]', 'eq', 'val1', 'field1', 'filter[field1]']
        ];
        $request = Request::create(
            'http://test.com?' . implode('&', array_keys($queryStringValues))
        );

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'filter%5Bfield1%5D=val1',
            $accessor->getQueryString()
        );

        self::assertCount(count($queryStringValues), $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'filter[field1]' => $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1]')
            ],
            $accessor->getGroup('filter')
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRequestBody()
    {
        $requestBody = [
            'prm1'   => 'val1',
            'prm2'   => ['=' => 'val2'],
            'prm3'   => ['!=' => 'val3'],
            'prm4'   => null,
            'prm5'   => ['=' => null],
            'filter' => [
                'address.country'       => 'US',
                'address.region'        => ['<>' => 'NY'],
                'address.defaultRegion' => ['<>' => 'LA'],
                'path1'                 => null,
                'path2'                 => ['=' => null]
            ]
        ];
        $request = Request::create(
            'http://test.com',
            'DELETE',
            $requestBody
        );

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'prm1=val1'
            . '&prm2=val2'
            . '&prm3%5Bneq%5D=val3'
            . '&prm4='
            . '&prm5='
            . '&filter%5Baddress%5D%5Bcountry%5D=US'
            . '&filter%5Baddress%5D%5Bregion%5D%5Bneq%5D=NY'
            . '&filter%5Baddress%5D%5BdefaultRegion%5D%5Bneq%5D=LA'
            . '&filter%5Bpath1%5D='
            . '&filter%5Bpath2%5D=',
            $accessor->getQueryString()
        );

        self::assertEquals(
            $this->getFilterValue('prm1', 'val1', 'eq', 'prm1'),
            $accessor->get('prm1'),
            'prm1'
        );
        self::assertEquals(
            $this->getFilterValue('prm2', 'val2', 'eq', 'prm2'),
            $accessor->get('prm2'),
            'prm2'
        );
        self::assertEquals(
            $this->getFilterValue('prm3', 'val3', 'neq', 'prm3'),
            $accessor->get('prm3'),
            'prm3'
        );
        self::assertEquals(
            $this->getFilterValue('prm4', '', 'eq', 'prm4'),
            $accessor->get('prm4'),
            'prm4'
        );
        self::assertEquals(
            $this->getFilterValue('prm5', '', 'eq', 'prm5'),
            $accessor->get('prm5'),
            'prm5'
        );
        self::assertEquals(
            $this->getFilterValue('address.country', 'US', 'eq', 'filter[address.country]'),
            $accessor->get('filter[address.country]'),
            'filter[address.country]'
        );
        self::assertEquals(
            $this->getFilterValue('address.region', 'NY', 'neq', 'filter[address.region]'),
            $accessor->get('filter[address.region]'),
            'filter[address.region]'
        );
        self::assertEquals(
            $this->getFilterValue('address.defaultRegion', 'LA', 'neq', 'filter[address.defaultRegion]'),
            $accessor->get('filter[address.defaultRegion]'),
            'filter[address.defaultRegion]'
        );
        self::assertEquals(
            $this->getFilterValue('path1', '', 'eq', 'filter[path1]'),
            $accessor->get('filter[path1]'),
            'filter[path1]'
        );
        self::assertEquals(
            $this->getFilterValue('path2', '', 'eq', 'filter[path2]'),
            $accessor->get('filter[path2]'),
            'filter[path2]'
        );

        self::assertCount(10, $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => $this->getFilterValue('prm1', 'val1', 'eq', 'prm1')
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'filter[address.country]'       => $this->getFilterValue(
                    'address.country',
                    'US',
                    'eq',
                    'filter[address.country]'
                ),
                'filter[address.region]'        => $this->getFilterValue(
                    'address.region',
                    'NY',
                    'neq',
                    'filter[address.region]'
                ),
                'filter[address.defaultRegion]' => $this->getFilterValue(
                    'address.defaultRegion',
                    'LA',
                    'neq',
                    'filter[address.defaultRegion]'
                ),
                'filter[path1]'                 => $this->getFilterValue(
                    'path1',
                    '',
                    'eq',
                    'filter[path1]'
                ),
                'filter[path2]'                 => $this->getFilterValue(
                    'path2',
                    '',
                    'eq',
                    'filter[path2]'
                )
            ],
            $accessor->getGroup('filter')
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRequestBodyWithAlternativeSyntaxOfFilters()
    {
        $requestBody = [
            'prm1'   => ['eq' => 'val1'],
            'prm2'   => ['neq' => 'val2'],
            'prm3'   => ['lt' => 'val3'],
            'prm4'   => ['lte' => 'val4'],
            'prm5'   => ['gt' => 'val5'],
            'prm6'   => ['gte' => 'val6'],
            'filter' => [
                'field1' => ['eq' => 'val1'],
                'field2' => ['neq' => 'val2'],
                'field3' => ['lt' => 'val3'],
                'field4' => ['lte' => 'val4'],
                'field5' => ['gt' => 'val5'],
                'field6' => ['gte' => 'val6']
            ]
        ];
        $request = Request::create(
            'http://test.com',
            'DELETE',
            $requestBody
        );

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'prm1=val1'
            . '&prm2%5Bneq%5D=val2'
            . '&prm3%5Blt%5D=val3'
            . '&prm4%5Blte%5D=val4'
            . '&prm5%5Bgt%5D=val5'
            . '&prm6%5Bgte%5D=val6'
            . '&filter%5Bfield1%5D=val1'
            . '&filter%5Bfield2%5D%5Bneq%5D=val2'
            . '&filter%5Bfield3%5D%5Blt%5D=val3'
            . '&filter%5Bfield4%5D%5Blte%5D=val4'
            . '&filter%5Bfield5%5D%5Bgt%5D=val5'
            . '&filter%5Bfield6%5D%5Bgte%5D=val6',
            $accessor->getQueryString()
        );

        self::assertEquals(
            $this->getFilterValue('prm1', 'val1', 'eq', 'prm1'),
            $accessor->get('prm1'),
            'prm1'
        );
        self::assertEquals(
            $this->getFilterValue('prm2', 'val2', 'neq', 'prm2'),
            $accessor->get('prm2'),
            'prm2'
        );
        self::assertEquals(
            $this->getFilterValue('prm3', 'val3', 'lt', 'prm3'),
            $accessor->get('prm3'),
            'prm3'
        );
        self::assertEquals(
            $this->getFilterValue('prm4', 'val4', 'lte', 'prm4'),
            $accessor->get('prm4'),
            'prm4'
        );
        self::assertEquals(
            $this->getFilterValue('prm5', 'val5', 'gt', 'prm5'),
            $accessor->get('prm5'),
            'prm5'
        );
        self::assertEquals(
            $this->getFilterValue('prm6', 'val6', 'gte', 'prm6'),
            $accessor->get('prm6'),
            'prm6'
        );

        self::assertEquals(
            $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1]'),
            $accessor->get('filter[field1]'),
            'filter[field1]'
        );
        self::assertEquals(
            $this->getFilterValue('field2', 'val2', 'neq', 'filter[field2]'),
            $accessor->get('filter[field2]'),
            'filter[field2]'
        );
        self::assertEquals(
            $this->getFilterValue('field3', 'val3', 'lt', 'filter[field3]'),
            $accessor->get('filter[field3]'),
            'filter[field3]'
        );
        self::assertEquals(
            $this->getFilterValue('field4', 'val4', 'lte', 'filter[field4]'),
            $accessor->get('filter[field4]'),
            'filter[field4]'
        );
        self::assertEquals(
            $this->getFilterValue('field5', 'val5', 'gt', 'filter[field5]'),
            $accessor->get('filter[field5]'),
            'filter[field5]'
        );
        self::assertEquals(
            $this->getFilterValue('field6', 'val6', 'gte', 'filter[field6]'),
            $accessor->get('filter[field6]'),
            'filter[field6]'
        );

        self::assertCount(12, $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => $this->getFilterValue('prm1', 'val1', 'eq', 'prm1')
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'filter[field1]' => $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1]'),
                'filter[field2]' => $this->getFilterValue('field2', 'val2', 'neq', 'filter[field2]'),
                'filter[field3]' => $this->getFilterValue('field3', 'val3', 'lt', 'filter[field3]'),
                'filter[field4]' => $this->getFilterValue('field4', 'val4', 'lte', 'filter[field4]'),
                'filter[field5]' => $this->getFilterValue('field5', 'val5', 'gt', 'filter[field5]'),
                'filter[field6]' => $this->getFilterValue('field6', 'val6', 'gte', 'filter[field6]')
            ],
            $accessor->getGroup('filter')
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "prm1", given "integer".
     */
    public function testRequestBodyWithNotStringScalarParameterValue()
    {
        $requestBody = [
            'prm1' => 1
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "prm1", given "array".
     */
    public function testRequestBodyWithArrayParameterValue()
    {
        $requestBody = [
            'prm1' => []
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "prm1", given "stdClass".
     */
    public function testRequestBodyWithWithObjectParameterValue()
    {
        $requestBody = [
            'prm1' => new \stdClass()
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "prm1", given "integer".
     */
    public function testRequestBodyWithNotStringScalarParameterValueWithOperator()
    {
        $requestBody = [
            'prm1' => ['neq' => 1]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "prm1", given "array".
     */
    public function testRequestBodyWithArrayParameterValueWithOperator()
    {
        $requestBody = [
            'prm1' => ['neq' => []]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "prm1", given "stdClass".
     */
    public function testRequestBodyWithObjectParameterValueWithOperator()
    {
        $requestBody = [
            'prm1' => ['neq' => new \stdClass()]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "filter[prm1]", given "integer".
     */
    public function testRequestBodyWithNestedParameterAndNotStringScalarParameterValue()
    {
        $requestBody = [
            'filter' => ['prm1' => 1]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "filter[prm1]", given "array".
     */
    public function testRequestBodyWithNestedParameterAndArrayParameterValue()
    {
        $requestBody = [
            'filter' => ['prm1' => []]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "filter[prm1]", given "stdClass".
     */
    public function testRequestBodyWithNestedParameterAndObjectParameterValue()
    {
        $requestBody = [
            'filter' => ['prm1' => new \stdClass()]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "filter[prm1]", given "integer".
     */
    public function testRequestBodyWithNestedParameterAndNotStringScalarParameterValueWithOperator()
    {
        $requestBody = [
            'filter' => ['prm1' => ['neq' => 1]]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "filter[prm1]", given "array".
     */
    public function testRequestBodyWithNestedParameterAndArrayParameterValueWithOperator()
    {
        $requestBody = [
            'filter' => ['prm1' => ['neq' => []]]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the filter "filter[prm1]", given "stdClass".
     */
    public function testRequestBodyWithNestedParameterAndObjectParameterValueWithOperator()
    {
        $requestBody = [
            'filter' => ['prm1' => ['neq' => new \stdClass()]]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testFilterFromQueryStringShouldOverrideFilterFromRequestBody()
    {
        $request = Request::create(
            'http://test.com?prm1=val1',
            'DELETE',
            ['prm1' => ['!=' => 'val2']]
        );
        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertCount(1, $accessor->getAll());
        self::assertEquals($this->getFilterValue('prm1', 'val1', 'eq', 'prm1'), $accessor->get('prm1'));

        self::assertEquals(
            'prm1=val1',
            $accessor->getQueryString()
        );
    }

    public function testOverrideExistingFilterValue()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?prm1=oldValue'));

        $existingFilterValue = $this->getFilterValue('prm1', 'oldValue', 'eq', 'prm1');
        self::assertEquals($existingFilterValue, $accessor->get('prm1'));

        $accessor->set('prm1', new FilterValue('prm1', 'newValue', 'eq'));

        $expectedFilterValue = new FilterValue('prm1', 'newValue', 'eq');
        $expectedFilterValue->setSource($existingFilterValue);
        self::assertEquals($expectedFilterValue, $accessor->get('prm1'));
        self::assertEquals(
            ['prm1' => $expectedFilterValue],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['prm1' => $expectedFilterValue],
            $accessor->getGroup('prm1'),
            'getGroup'
        );

        self::assertEquals(
            'prm1=oldValue',
            $accessor->getQueryString()
        );
    }

    public function testOverrideExistingGroupedFilterValue()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?group[path]=oldValue'));

        $existingFilterValue = $this->getFilterValue('path', 'oldValue', 'eq', 'group[path]');
        self::assertEquals($existingFilterValue, $accessor->get('group[path]'));

        $accessor->set('group[path]', new FilterValue('path', 'neValue', 'eq'));

        $expectedFilterValue = new FilterValue('path', 'neValue', 'eq');
        $expectedFilterValue->setSource($existingFilterValue);
        self::assertEquals($expectedFilterValue, $accessor->get('group[path]'));
        self::assertEquals(
            ['group[path]' => $expectedFilterValue],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['group[path]' => $expectedFilterValue],
            $accessor->getGroup('group'),
            'getGroup'
        );

        self::assertEquals(
            'group%5Bpath%5D=oldValue',
            $accessor->getQueryString()
        );
    }

    public function testAddNewFilterValue()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com'));

        $accessor->set('prm1', new FilterValue('prm1', 'val1', 'eq'));
        self::assertEquals(new FilterValue('prm1', 'val1', 'eq'), $accessor->get('prm1'));
        self::assertEquals(
            ['prm1' => new FilterValue('prm1', 'val1', 'eq')],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['prm1' => new FilterValue('prm1', 'val1', 'eq')],
            $accessor->getGroup('prm1'),
            'getGroup'
        );

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testAddNewGroupedFilterValue()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com'));

        $accessor->set('group[path]', new FilterValue('path', 'val1', 'eq'));
        self::assertEquals(new FilterValue('path', 'val1', 'eq'), $accessor->get('group[path]'));
        self::assertEquals(
            ['group[path]' => new FilterValue('path', 'val1', 'eq')],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['group[path]' => new FilterValue('path', 'val1', 'eq')],
            $accessor->getGroup('group'),
            'getGroup'
        );

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingFilterValueViaSetMethod()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?prm1=val1'));

        self::assertEquals($this->getFilterValue('prm1', 'val1', 'eq', 'prm1'), $accessor->get('prm1'));

        // test override existing filter value
        $accessor->set('prm1', null);
        self::assertNull($accessor->get('prm1'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('prm1'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingGroupedFilterValueViaSetMethod()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?group[path]=val1'));

        self::assertEquals($this->getFilterValue('path', 'val1', 'eq', 'group[path]'), $accessor->get('group[path]'));

        // test override existing filter value
        $accessor->set('group[path]', null);
        self::assertNull($accessor->get('group[path]'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('group'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingFilterValueViaRemoveMethod()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?prm1=val1'));

        self::assertEquals($this->getFilterValue('prm1', 'val1', 'eq', 'prm1'), $accessor->get('prm1'));

        // test remove existing filter value by key
        $accessor->remove('prm1');
        self::assertNull($accessor->get('prm1'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('prm1'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingGroupedFilterValueViaRemoveMethod()
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?group[path]=val1'));

        self::assertEquals($this->getFilterValue('path', 'val1', 'eq', 'group[path]'), $accessor->get('group[path]'));

        // test remove existing filter value by key
        $accessor->remove('group[path]');
        self::assertNull($accessor->get('group[path]'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('group'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testDefaultGroup()
    {
        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com?filter1=val1&filter[filter2]=val2')
        );

        self::assertNull($accessor->getDefaultGroupName());

        $accessor->setDefaultGroupName('filter');
        self::assertEquals('filter', $accessor->getDefaultGroupName());

        self::assertTrue($accessor->has('filter1'));
        self::assertFalse($accessor->has('filter[filter1]'));
        self::assertTrue($accessor->has('filter2'));
        self::assertTrue($accessor->has('filter[filter2]'));

        $filter1 = FilterValue::createFromSource('filter1', 'filter1', 'val1', 'eq');
        $filter2 = FilterValue::createFromSource('filter[filter2]', 'filter2', 'val2', 'eq');

        self::assertEquals($filter1, $accessor->get('filter1'));
        self::assertNull($accessor->get('filter[filter1]'));
        self::assertEquals($filter2, $accessor->get('filter2'));
        self::assertEquals($filter2, $accessor->get('filter[filter2]'));

        self::assertEquals(
            'filter1=val1'
            . '&filter%5Bfilter2%5D=val2',
            $accessor->getQueryString()
        );
    }
}
