<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestFilterValueAccessorTest extends \PHPUnit\Framework\TestCase
{
    private function getRestFilterValueAccessor(Request $request): RestFilterValueAccessor
    {
        $accessor = new RestFilterValueAccessor(
            $request,
            [
                FilterOperator::EQ              => '=',
                FilterOperator::NEQ             => '!=',
                FilterOperator::GT              => '>',
                FilterOperator::LT              => '<',
                FilterOperator::GTE             => '>=',
                FilterOperator::LTE             => '<=',
                FilterOperator::EXISTS          => '*',
                FilterOperator::NEQ_OR_NULL     => '!*',
                FilterOperator::CONTAINS        => '~',
                FilterOperator::NOT_CONTAINS    => '!~',
                FilterOperator::STARTS_WITH     => '^',
                FilterOperator::NOT_STARTS_WITH => '!^',
                FilterOperator::ENDS_WITH       => '$',
                FilterOperator::NOT_ENDS_WITH   => '!$',
                FilterOperator::EMPTY_VALUE     => null
            ],
            ['test_key']
        );
        $accessor->enableRequestBodyParsing();

        return $accessor;
    }

    private function getFilterValue(string $path, mixed $value, string $operator, string $sourceKey): FilterValue
    {
        return FilterValue::createFromSource($sourceKey, $path, $value, $operator);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testParseQueryString(): void
    {
        $queryStringValues = [
            'prm1[eq]=val1'              => ['prm1', 'eq', 'val1', 'prm1', 'prm1[eq]'],
            'prm1[neq]=val1_1'           => ['prm1', 'neq', 'val1_1', 'prm1', 'prm1[neq]'],
            'prm2[neq]=val2'             => ['prm2', 'neq', 'val2', 'prm2', 'prm2[neq]'],
            'prm3[lt]=val3'              => ['prm3', 'lt', 'val3', 'prm3', 'prm3[lt]'],
            'prm4[lte]=val4'             => ['prm4', 'lte', 'val4', 'prm4', 'prm4[lte]'],
            'prm5[gt]=val5'              => ['prm5', 'gt', 'val5', 'prm5', 'prm5[gt]'],
            'prm6[gte]=val6'             => ['prm6', 'gte', 'val6', 'prm6', 'prm6[gte]'],
            'prm7[empty]=yes'            => ['prm7', 'empty', 'yes', 'prm7', 'prm7[empty]'],
            'filter[field1][eq]=val1'    => ['filter[field1]', 'eq', 'val1', 'field1', 'filter[field1][eg]'],
            'filter[field1][neq]=val1_1' => ['filter[field1]', 'neq', 'val1_1', 'field1', 'filter[field1][neq]'],
            'filter[field2][neq]=val2'   => ['filter[field2]', 'neq', 'val2', 'field2', 'filter[field2][neq]'],
            'filter[field3][lt]=val3'    => ['filter[field3]', 'lt', 'val3', 'field3', 'filter[field3][lt]'],
            'filter[field4][lte]=val4'   => ['filter[field4]', 'lte', 'val4', 'field4', 'filter[field4][lte]'],
            'filter[field5][gt]=val5'    => ['filter[field5]', 'gt', 'val5', 'field5', 'filter[field5][gt]'],
            'filter[field6][gte]=val6'   => ['filter[field6]', 'gte', 'val6', 'field6', 'filter[field6][gte]']
        ];
        $request = Request::create('http://test.com?' . implode('&', array_keys($queryStringValues)));

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'filter%5Bfield1%5D=val1'
            . '&filter%5Bfield1%5D%5Bneq%5D=val1_1'
            . '&filter%5Bfield2%5D%5Bneq%5D=val2'
            . '&filter%5Bfield3%5D%5Blt%5D=val3'
            . '&filter%5Bfield4%5D%5Blte%5D=val4'
            . '&filter%5Bfield5%5D%5Bgt%5D=val5'
            . '&filter%5Bfield6%5D%5Bgte%5D=val6'
            . '&prm1=val1'
            . '&prm1%5Bneq%5D=val1_1'
            . '&prm2%5Bneq%5D=val2'
            . '&prm3%5Blt%5D=val3'
            . '&prm4%5Blte%5D=val4'
            . '&prm5%5Bgt%5D=val5'
            . '&prm6%5Bgte%5D=val6'
            . '&prm7%5Bempty%5D=yes',
            $accessor->getQueryString()
        );

        foreach ($queryStringValues as $itemKey => $itemValue) {
            [$key, $operator, $value, $path, $sourceKey] = $itemValue;
            self::assertTrue($accessor->has($key), sprintf('has - %s', $itemKey));
            if ('prm1' === $key || 'filter[field1]' === $key) {
                continue;
            }
            self::assertEquals(
                [$this->getFilterValue($path, $value, $operator, $sourceKey)],
                $accessor->get($key),
                $itemKey
            );
        }
        self::assertEquals(
            [
                $this->getFilterValue('prm1', 'val1', 'eq', 'prm1[eq]'),
                $this->getFilterValue('prm1', 'val1_1', 'neq', 'prm1[neq]')
            ],
            $accessor->get('prm1'),
            'prm1'
        );
        self::assertEquals(
            [
                $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1][eq]'),
                $this->getFilterValue('field1', 'val1_1', 'neq', 'filter[field1][neq]')
            ],
            $accessor->get('filter[field1]'),
            'filter[field1]'
        );

        self::assertFalse($accessor->has('unknown'), 'has - unknown');
        self::assertCount(0, $accessor->get('unknown'), 'get - unknown');

        self::assertCount(count($queryStringValues) - 2, $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => [
                    $this->getFilterValue('prm1', 'val1', 'eq', 'prm1[eq]'),
                    $this->getFilterValue('prm1', 'val1_1', 'neq', 'prm1[neq]')
                ]
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'filter[field1]' => [
                    $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1][eq]'),
                    $this->getFilterValue('field1', 'val1_1', 'neq', 'filter[field1][neq]')
                ],
                'filter[field2]' => [$this->getFilterValue('field2', 'val2', 'neq', 'filter[field2][neq]')],
                'filter[field3]' => [$this->getFilterValue('field3', 'val3', 'lt', 'filter[field3][lt]')],
                'filter[field4]' => [$this->getFilterValue('field4', 'val4', 'lte', 'filter[field4][lte]')],
                'filter[field5]' => [$this->getFilterValue('field5', 'val5', 'gt', 'filter[field5][gt]')],
                'filter[field6]' => [$this->getFilterValue('field6', 'val6', 'gte', 'filter[field6][gte]')]
            ],
            $accessor->getGroup('filter')
        );
    }

    public function testParseUrlQueryStringWithTestKeyThatShouldBeSkipped(): void
    {
        $request = Request::create('http://test.com?test_key=test_value');

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals('', $accessor->getQueryString());
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertEquals(
            [],
            $accessor->getGroup('filter')
        );
    }

    public function testParseUrlEncodedQueryString(): void
    {
        $queryStringValues = [
            'filter%5Bfield1%5D%5Beq%5D=val1' => ['filter[field1]', 'eq', 'val1', 'field1', 'filter[field1]']
        ];
        $request = Request::create('http://test.com?' . implode('&', array_keys($queryStringValues)));

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'filter%5Bfield1%5D=val1',
            $accessor->getQueryString()
        );

        self::assertCount(count($queryStringValues), $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'filter[field1]' => [$this->getFilterValue('field1', 'val1', 'eq', 'filter[field1][eq]')]
            ],
            $accessor->getGroup('filter')
        );
    }

    public function testParseQueryStringWithEmptyValues(): void
    {
        $queryStringValues = [
            'prm1='             => ['prm1', 'eq', '', 'prm1', 'prm1'],
            'group1[key]='      => ['group1[key]', 'eq', '', 'key', 'group1[key]'],
            'group2%5Bkey%5D='  => ['group2[key]', 'eq', '', 'key', 'group2[key]'],
            'group3[key1]='     => ['group3[key1]', 'eq', '', 'key1', 'group3[key1]'],
            'group3%5Bkey2%5D=' => ['group3[key2]', 'eq', '', 'key2', 'group3[key2]']
        ];
        $request = Request::create('http://test.com?' . implode('&', array_keys($queryStringValues)));

        $accessor = $this->getRestFilterValueAccessor($request);

        $expectedQueryString =
            'group1%5Bkey%5D='
            . '&group2%5Bkey%5D='
            . '&group3%5Bkey1%5D='
            . '&group3%5Bkey2%5D='
            . '&prm1=';
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
            [$key, $operator, $value, $path, $sourceKey] = $itemValue;
            self::assertTrue($accessor->has($key), sprintf('has - %s', $itemKey));
            self::assertEquals(
                [$this->getFilterValue($path, $value, $operator, $sourceKey)],
                $accessor->get($key),
                $itemKey
            );
        }

        self::assertCount(count($queryStringValues), $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => [$this->getFilterValue('prm1', '', 'eq', 'prm1')]
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'group1[key]' => [$this->getFilterValue('key', '', 'eq', 'group1[key]')]
            ],
            $accessor->getGroup('group1')
        );
        self::assertEquals(
            [
                'group2[key]' => [$this->getFilterValue('key', '', 'eq', 'group2[key]')]
            ],
            $accessor->getGroup('group2')
        );
        self::assertEquals(
            [
                'group3[key1]' => [$this->getFilterValue('key1', '', 'eq', 'group3[key1]')],
                'group3[key2]' => [$this->getFilterValue('key2', '', 'eq', 'group3[key2]')]
            ],
            $accessor->getGroup('group3')
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRequestBody(): void
    {
        $requestBody = [
            'prm1'   => ['eq' => 'val1', 'neq' => 'val1_1'],
            'prm2'   => ['neq' => 'val2'],
            'prm3'   => ['lt' => 'val3'],
            'prm4'   => ['lte' => 'val4'],
            'prm5'   => ['gt' => 'val5'],
            'prm6'   => ['gte' => 'val6'],
            'filter' => [
                'field1' => ['eq' => 'val1', 'neq' => 'val1_1'],
                'field2' => ['neq' => 'val2'],
                'field3' => ['lt' => 'val3'],
                'field4' => ['lte' => 'val4'],
                'field5' => ['gt' => 'val5'],
                'field6' => ['gte' => 'val6']
            ]
        ];
        $request = Request::create('http://test.com', 'DELETE', $requestBody);

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'prm1=val1'
            . '&prm1%5Bneq%5D=val1_1'
            . '&prm2%5Bneq%5D=val2'
            . '&prm3%5Blt%5D=val3'
            . '&prm4%5Blte%5D=val4'
            . '&prm5%5Bgt%5D=val5'
            . '&prm6%5Bgte%5D=val6'
            . '&filter%5Bfield1%5D=val1'
            . '&filter%5Bfield1%5D%5Bneq%5D=val1_1'
            . '&filter%5Bfield2%5D%5Bneq%5D=val2'
            . '&filter%5Bfield3%5D%5Blt%5D=val3'
            . '&filter%5Bfield4%5D%5Blte%5D=val4'
            . '&filter%5Bfield5%5D%5Bgt%5D=val5'
            . '&filter%5Bfield6%5D%5Bgte%5D=val6',
            $accessor->getQueryString()
        );

        self::assertEquals(
            [
                $this->getFilterValue('prm1', 'val1', 'eq', 'prm1[eq]'),
                $this->getFilterValue('prm1', 'val1_1', 'neq', 'prm1[neq]')
            ],
            $accessor->get('prm1'),
            'prm1'
        );
        self::assertEquals(
            [$this->getFilterValue('prm2', 'val2', 'neq', 'prm2[neq]')],
            $accessor->get('prm2'),
            'prm2'
        );
        self::assertEquals(
            [$this->getFilterValue('prm3', 'val3', 'lt', 'prm3[lt]')],
            $accessor->get('prm3'),
            'prm3'
        );
        self::assertEquals(
            [$this->getFilterValue('prm4', 'val4', 'lte', 'prm4[lte]')],
            $accessor->get('prm4'),
            'prm4'
        );
        self::assertEquals(
            [$this->getFilterValue('prm5', 'val5', 'gt', 'prm5[gt]')],
            $accessor->get('prm5'),
            'prm5'
        );
        self::assertEquals(
            [$this->getFilterValue('prm6', 'val6', 'gte', 'prm6[gte]')],
            $accessor->get('prm6'),
            'prm6'
        );

        self::assertEquals(
            [
                $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1][eq]'),
                $this->getFilterValue('field1', 'val1_1', 'neq', 'filter[field1][neq]')
            ],
            $accessor->get('filter[field1]'),
            'filter[field1]'
        );
        self::assertEquals(
            [$this->getFilterValue('field2', 'val2', 'neq', 'filter[field2][neq]')],
            $accessor->get('filter[field2]'),
            'filter[field2]'
        );
        self::assertEquals(
            [$this->getFilterValue('field3', 'val3', 'lt', 'filter[field3][lt]')],
            $accessor->get('filter[field3]'),
            'filter[field3]'
        );
        self::assertEquals(
            [$this->getFilterValue('field4', 'val4', 'lte', 'filter[field4][lte]')],
            $accessor->get('filter[field4]'),
            'filter[field4]'
        );
        self::assertEquals(
            [$this->getFilterValue('field5', 'val5', 'gt', 'filter[field5][gt]')],
            $accessor->get('filter[field5]'),
            'filter[field5]'
        );
        self::assertEquals(
            [$this->getFilterValue('field6', 'val6', 'gte', 'filter[field6][gte]')],
            $accessor->get('filter[field6]'),
            'filter[field6]'
        );

        self::assertCount(12, $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => [
                    $this->getFilterValue('prm1', 'val1', 'eq', 'prm1[eq]'),
                    $this->getFilterValue('prm1', 'val1_1', 'neq', 'prm1[neq]')
                ]
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'filter[field1]' => [
                    $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1][eq]'),
                    $this->getFilterValue('field1', 'val1_1', 'neq', 'filter[field1][neq]')
                ],
                'filter[field2]' => [$this->getFilterValue('field2', 'val2', 'neq', 'filter[field2][neq]')],
                'filter[field3]' => [$this->getFilterValue('field3', 'val3', 'lt', 'filter[field3][lt]')],
                'filter[field4]' => [$this->getFilterValue('field4', 'val4', 'lte', 'filter[field4][lte]')],
                'filter[field5]' => [$this->getFilterValue('field5', 'val5', 'gt', 'filter[field5][gt]')],
                'filter[field6]' => [$this->getFilterValue('field6', 'val6', 'gte', 'filter[field6][gte]')]
            ],
            $accessor->getGroup('filter')
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRequestBodyForNotStructuredData(): void
    {
        $requestBody = [
            'prm1[eq]'            => 'val1',
            'prm1[neq]'           => 'val1_1',
            'prm2[neq]'           => 'val2',
            'prm3[lt]'            => 'val3',
            'prm4[lte]'           => 'val4',
            'prm5[gt]'            => 'val5',
            'prm6[gte]'           => 'val6',
            'prm7'                => 'val7',
            'prm7[neq]'           => 'val7_1',
            'prm8'                => 'val8',
            'filter[field1][eq]'  => 'val1',
            'filter[field1][neq]' => 'val1_1',
            'filter[field2][neq]' => 'val2',
            'filter[field3][lt]'  => 'val3',
            'filter[field4][lte]' => 'val4',
            'filter[field5][gt]'  => 'val5',
            'filter[field6][gte]' => 'val6',
            'filter[field7]'      => 'val7',
            'filter[field7][neq]' => 'val7_1',
            'filter[field8]'      => 'val8'
        ];
        $request = Request::create('http://test.com', 'DELETE', $requestBody);

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'prm1=val1'
            . '&prm1%5Bneq%5D=val1_1'
            . '&prm2%5Bneq%5D=val2'
            . '&prm3%5Blt%5D=val3'
            . '&prm4%5Blte%5D=val4'
            . '&prm5%5Bgt%5D=val5'
            . '&prm6%5Bgte%5D=val6'
            . '&prm7=val7'
            . '&prm7%5Bneq%5D=val7_1'
            . '&prm8=val8'
            . '&filter%5Bfield1%5D=val1'
            . '&filter%5Bfield1%5D%5Bneq%5D=val1_1'
            . '&filter%5Bfield2%5D%5Bneq%5D=val2'
            . '&filter%5Bfield3%5D%5Blt%5D=val3'
            . '&filter%5Bfield4%5D%5Blte%5D=val4'
            . '&filter%5Bfield5%5D%5Bgt%5D=val5'
            . '&filter%5Bfield6%5D%5Bgte%5D=val6'
            . '&filter%5Bfield7%5D=val7'
            . '&filter%5Bfield7%5D%5Bneq%5D=val7_1'
            . '&filter%5Bfield8%5D=val8',
            $accessor->getQueryString()
        );

        self::assertEquals(
            [
                $this->getFilterValue('prm1', 'val1', 'eq', 'prm1[eq]'),
                $this->getFilterValue('prm1', 'val1_1', 'neq', 'prm1[neq]')
            ],
            $accessor->get('prm1'),
            'prm1'
        );
        self::assertEquals(
            [$this->getFilterValue('prm2', 'val2', 'neq', 'prm2[neq]')],
            $accessor->get('prm2'),
            'prm2'
        );
        self::assertEquals(
            [$this->getFilterValue('prm3', 'val3', 'lt', 'prm3[lt]')],
            $accessor->get('prm3'),
            'prm3'
        );
        self::assertEquals(
            [$this->getFilterValue('prm4', 'val4', 'lte', 'prm4[lte]')],
            $accessor->get('prm4'),
            'prm4'
        );
        self::assertEquals(
            [$this->getFilterValue('prm5', 'val5', 'gt', 'prm5[gt]')],
            $accessor->get('prm5'),
            'prm5'
        );
        self::assertEquals(
            [$this->getFilterValue('prm6', 'val6', 'gte', 'prm6[gte]')],
            $accessor->get('prm6'),
            'prm6'
        );
        self::assertEquals(
            [
                $this->getFilterValue('prm7', 'val7', 'eq', 'prm7[eq]'),
                $this->getFilterValue('prm7', 'val7_1', 'neq', 'prm7[neq]')
            ],
            $accessor->get('prm7'),
            'prm7'
        );
        self::assertEquals(
            [$this->getFilterValue('prm8', 'val8', 'eq', 'prm8')],
            $accessor->get('prm8'),
            'prm8'
        );

        self::assertEquals(
            [
                $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1][eq]'),
                $this->getFilterValue('field1', 'val1_1', 'neq', 'filter[field1][neq]')
            ],
            $accessor->get('filter[field1]'),
            'filter[field1]'
        );
        self::assertEquals(
            [$this->getFilterValue('field2', 'val2', 'neq', 'filter[field2][neq]')],
            $accessor->get('filter[field2]'),
            'filter[field2]'
        );
        self::assertEquals(
            [$this->getFilterValue('field3', 'val3', 'lt', 'filter[field3][lt]')],
            $accessor->get('filter[field3]'),
            'filter[field3]'
        );
        self::assertEquals(
            [$this->getFilterValue('field4', 'val4', 'lte', 'filter[field4][lte]')],
            $accessor->get('filter[field4]'),
            'filter[field4]'
        );
        self::assertEquals(
            [$this->getFilterValue('field5', 'val5', 'gt', 'filter[field5][gt]')],
            $accessor->get('filter[field5]'),
            'filter[field5]'
        );
        self::assertEquals(
            [$this->getFilterValue('field6', 'val6', 'gte', 'filter[field6][gte]')],
            $accessor->get('filter[field6]'),
            'filter[field6]'
        );
        self::assertEquals(
            [
                $this->getFilterValue('field7', 'val7', 'eq', 'filter[field7][eq]'),
                $this->getFilterValue('field7', 'val7_1', 'neq', 'filter[field7][neq]')
            ],
            $accessor->get('filter[field7]'),
            'filter[field7]'
        );
        self::assertEquals(
            [$this->getFilterValue('field8', 'val8', 'eq', 'filter[field8][eq]')],
            $accessor->get('filter[field8]'),
            'filter[field8]'
        );

        self::assertCount(16, $accessor->getAll(), 'getAll');
        self::assertEquals(
            [
                'prm1' => [
                    $this->getFilterValue('prm1', 'val1', 'eq', 'prm1[eq]'),
                    $this->getFilterValue('prm1', 'val1_1', 'neq', 'prm1[neq]')
                ]
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'prm7' => [
                    $this->getFilterValue('prm7', 'val7', 'eq', 'prm7[eq]'),
                    $this->getFilterValue('prm7', 'val7_1', 'neq', 'prm7[neq]')
                ]
            ],
            $accessor->getGroup('prm7')
        );
        self::assertEquals(
            [
                'filter[field1]' => [
                    $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1][eq]'),
                    $this->getFilterValue('field1', 'val1_1', 'neq', 'filter[field1][neq]')
                ],
                'filter[field2]' => [$this->getFilterValue('field2', 'val2', 'neq', 'filter[field2][neq]')],
                'filter[field3]' => [$this->getFilterValue('field3', 'val3', 'lt', 'filter[field3][lt]')],
                'filter[field4]' => [$this->getFilterValue('field4', 'val4', 'lte', 'filter[field4][lte]')],
                'filter[field5]' => [$this->getFilterValue('field5', 'val5', 'gt', 'filter[field5][gt]')],
                'filter[field6]' => [$this->getFilterValue('field6', 'val6', 'gte', 'filter[field6][gte]')],
                'filter[field7]' => [
                    $this->getFilterValue('field7', 'val7', 'eq', 'filter[field7][eq]'),
                    $this->getFilterValue('field7', 'val7_1', 'neq', 'filter[field7][neq]')
                ],
                'filter[field8]' => [$this->getFilterValue('field8', 'val8', 'eq', 'filter[field8][eq]')]
            ],
            $accessor->getGroup('filter')
        );
    }

    /**
     * @dataProvider requestBodyWithUnexpectedNotStructuredDataProvider
     */
    public function testRequestBodyWithUnexpectedNotStructuredData(
        array $requestBody,
        string $queryString
    ): void {
        $request = Request::create('http://test.com', 'DELETE', $requestBody);

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals($queryString, $accessor->getQueryString());

        $paramName = key($requestBody);
        self::assertEquals(
            [$this->getFilterValue($paramName, 'val', 'eq', $paramName)],
            $accessor->get($paramName)
        );
    }

    public static function requestBodyWithUnexpectedNotStructuredDataProvider(): array
    {
        return [
            [['prm1[eq' => 'val'], 'prm1%5Beq=val'],
            [['prm1]' => 'val'], 'prm1%5D=val'],
            [['filter[field][eq' => 'val'], 'filter%5Bfield%5D%5Beq=val'],
            [['filter[field]]' => 'val'], 'filter%5Bfield%5D%5D=val'],
            [['filter[field]eq]' => 'val'], 'filter%5Bfield%5Deq%5D=val']
        ];
    }

    public function testRequestBodyWithEmptyValues(): void
    {
        $requestBody = [
            'prm1'   => '',
            'group1' => ['key' => ''],
            'group2' => ['key' => ''],
            'group3' => ['key1' => '', 'key2' => '']
        ];
        $request = Request::create('http://test.com', 'DELETE', $requestBody);

        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertEquals(
            'prm1='
            . '&group1%5Bkey%5D='
            . '&group2%5Bkey%5D='
            . '&group3%5Bkey1%5D='
            . '&group3%5Bkey2%5D=',
            $accessor->getQueryString()
        );

        self::assertEquals(
            [$this->getFilterValue('prm1', '', 'eq', 'prm1')],
            $accessor->get('prm1'),
            'prm1'
        );
        self::assertEquals(
            [$this->getFilterValue('key', '', 'eq', 'group1[key]')],
            $accessor->get('group1[key]'),
            'group1[key]'
        );
        self::assertEquals(
            [$this->getFilterValue('key', '', 'eq', 'group2[key]')],
            $accessor->get('group2[key]'),
            'group2[key]'
        );
        self::assertEquals(
            [$this->getFilterValue('key1', '', 'eq', 'group3[key1]')],
            $accessor->get('group3[key1]'),
            'group3[key1]'
        );
        self::assertEquals(
            [$this->getFilterValue('key2', '', 'eq', 'group3[key2]')],
            $accessor->get('group3[key2]'),
            'group3[key2]'
        );
        self::assertEquals(
            [
                'prm1' => [$this->getFilterValue('prm1', '', 'eq', 'prm1')]
            ],
            $accessor->getGroup('prm1')
        );
        self::assertEquals(
            [
                'group1[key]' => [$this->getFilterValue('key', '', 'eq', 'group1[key]')]
            ],
            $accessor->getGroup('group1')
        );
        self::assertEquals(
            [
                'group2[key]' => [$this->getFilterValue('key', '', 'eq', 'group2[key]')]
            ],
            $accessor->getGroup('group2')
        );
        self::assertEquals(
            [
                'group3[key1]' => [$this->getFilterValue('key1', '', 'eq', 'group3[key1]')],
                'group3[key2]' => [$this->getFilterValue('key2', '', 'eq', 'group3[key2]')]
            ],
            $accessor->getGroup('group3')
        );
    }

    public function testRequestBodyWithNotStringScalarParameterValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "prm1", given "int".');

        $requestBody = [
            'prm1' => 1
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithArrayParameterValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "prm1", given "array".');

        $requestBody = [
            'prm1' => []
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithWithObjectParameterValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "prm1", given "stdClass".');

        $requestBody = [
            'prm1' => new \stdClass()
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithNotStringScalarParameterValueWithOperator(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "prm1[neq]", given "int".');

        $requestBody = [
            'prm1' => ['neq' => 1]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithArrayParameterValueWithOperator(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "prm1[neq]", given "array".');

        $requestBody = [
            'prm1' => ['neq' => []]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithObjectParameterValueWithOperator(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "prm1[neq]", given "stdClass".');

        $requestBody = [
            'prm1' => ['neq' => new \stdClass()]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithNestedParameterAndNotStringScalarParameterValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "filter[prm1]", given "int".');

        $requestBody = [
            'filter' => ['prm1' => 1]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithNestedParameterAndArrayParameterValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "filter[prm1]", given "array".');

        $requestBody = [
            'filter' => ['prm1' => []]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithNestedParameterAndObjectParameterValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "filter[prm1]", given "stdClass".');

        $requestBody = [
            'filter' => ['prm1' => new \stdClass()]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithNestedParameterAndNotStringScalarParameterValueWithOperator(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "filter[prm1][neq]", given "int".');

        $requestBody = [
            'filter' => ['prm1' => ['neq' => 1]]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithNestedParameterAndArrayParameterValueWithOperator(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "filter[prm1][neq]", given "array".');

        $requestBody = [
            'filter' => ['prm1' => ['neq' => []]]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testRequestBodyWithNestedParameterAndObjectParameterValueWithOperator(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected string value for the filter "filter[prm1][neq]", given "stdClass".');

        $requestBody = [
            'filter' => ['prm1' => ['neq' => new \stdClass()]]
        ];

        $accessor = $this->getRestFilterValueAccessor(
            Request::create('http://test.com', 'DELETE', $requestBody)
        );
        $accessor->getAll();
    }

    public function testFilterFromQueryStringShouldOverrideFilterFromRequestBody(): void
    {
        $request = Request::create(
            'http://test.com?prm1=val1',
            'DELETE',
            ['prm1' => ['neq' => 'val2', 'eq' => 'val3']]
        );
        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertCount(1, $accessor->getAll());
        self::assertEquals(
            [
                $this->getFilterValue('prm1', 'val2', 'neq', 'prm1[neq]'),
                $this->getFilterValue('prm1', 'val1', 'eq', 'prm1')
            ],
            $accessor->get('prm1')
        );

        self::assertEquals(
            'prm1%5Bneq%5D=val2&prm1=val1',
            $accessor->getQueryString()
        );
    }

    public function testGroupedFilterFromQueryStringShouldOverrideFilterFromRequestBody(): void
    {
        $request = Request::create(
            'http://test.com?filter[field1]=val1',
            'DELETE',
            ['filter' => ['field1' => ['neq' => 'val2', 'eq' => 'val3']]]
        );
        $accessor = $this->getRestFilterValueAccessor($request);

        self::assertCount(1, $accessor->getAll());
        self::assertEquals(
            [
                $this->getFilterValue('field1', 'val2', 'neq', 'filter[field1][neq]'),
                $this->getFilterValue('field1', 'val1', 'eq', 'filter[field1]')
            ],
            $accessor->get('filter[field1]')
        );

        self::assertEquals(
            'filter%5Bfield1%5D%5Bneq%5D=val2&filter%5Bfield1%5D=val1',
            $accessor->getQueryString()
        );
    }

    public function testFilterFromRequestBodyShouldByIgnoredWhenRequestBodyParsingDisabled(): void
    {
        $request = Request::create(
            'http://test.com?prm1=val1',
            'DELETE',
            ['prm1' => ['neq' => 'val2', 'eq' => 'val3']]
        );
        $accessor = $this->getRestFilterValueAccessor($request);
        $accessor->disableRequestBodyParsing();

        self::assertCount(1, $accessor->getAll());
        self::assertEquals(
            [
                $this->getFilterValue('prm1', 'val1', 'eq', 'prm1')
            ],
            $accessor->get('prm1')
        );

        self::assertEquals(
            'prm1=val1',
            $accessor->getQueryString()
        );
    }

    public function testOverrideExistingFilterValue(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?prm1=oldValue'));

        $existingFilterValue = $this->getFilterValue('prm1', 'oldValue', 'eq', 'prm1');
        self::assertEquals([$existingFilterValue], $accessor->get('prm1'));

        $accessor->set('prm1', new FilterValue('prm1', 'newValue', 'eq'));

        $expectedFilterValue = new FilterValue('prm1', 'newValue', 'eq');
        $expectedFilterValue->setSource($existingFilterValue);
        self::assertEquals([$expectedFilterValue], $accessor->get('prm1'));
        self::assertEquals(
            ['prm1' => [$expectedFilterValue]],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['prm1' => [$expectedFilterValue]],
            $accessor->getGroup('prm1'),
            'getGroup'
        );

        self::assertEquals(
            'prm1=oldValue',
            $accessor->getQueryString()
        );
    }

    public function testOverrideExistingGroupedFilterValue(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?group[path]=oldValue'));

        $existingFilterValue = $this->getFilterValue('path', 'oldValue', 'eq', 'group[path]');
        self::assertEquals([$existingFilterValue], $accessor->get('group[path]'));

        $accessor->set('group[path]', new FilterValue('path', 'neValue', 'eq'));

        $expectedFilterValue = new FilterValue('path', 'neValue', 'eq');
        $expectedFilterValue->setSource($existingFilterValue);
        self::assertEquals([$expectedFilterValue], $accessor->get('group[path]'));
        self::assertEquals(
            ['group[path]' => [$expectedFilterValue]],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['group[path]' => [$expectedFilterValue]],
            $accessor->getGroup('group'),
            'getGroup'
        );

        self::assertEquals(
            'group%5Bpath%5D=oldValue',
            $accessor->getQueryString()
        );
    }

    public function testAddNewFilterValue(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com'));

        $accessor->set('prm1', new FilterValue('prm1', 'val1', 'eq'));
        self::assertEquals([new FilterValue('prm1', 'val1', 'eq')], $accessor->get('prm1'));
        self::assertEquals(
            ['prm1' => [new FilterValue('prm1', 'val1', 'eq')]],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['prm1' => [new FilterValue('prm1', 'val1', 'eq')]],
            $accessor->getGroup('prm1'),
            'getGroup'
        );

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testAddNewGroupedFilterValue(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com'));

        $accessor->set('group[path]', new FilterValue('path', 'val1', 'eq'));
        self::assertEquals([new FilterValue('path', 'val1', 'eq')], $accessor->get('group[path]'));
        self::assertEquals(
            ['group[path]' => [new FilterValue('path', 'val1', 'eq')]],
            $accessor->getAll(),
            'getAll'
        );
        self::assertEquals(
            ['group[path]' => [new FilterValue('path', 'val1', 'eq')]],
            $accessor->getGroup('group'),
            'getGroup'
        );

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingFilterValueViaSetMethod(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?prm1=val1'));

        self::assertEquals([$this->getFilterValue('prm1', 'val1', 'eq', 'prm1')], $accessor->get('prm1'));

        // test override existing filter value
        $accessor->set('prm1', null);
        self::assertCount(0, $accessor->get('prm1'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('prm1'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingGroupedFilterValueViaSetMethod(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?group[path]=val1'));

        self::assertEquals(
            [$this->getFilterValue('path', 'val1', 'eq', 'group[path]')],
            $accessor->get('group[path]')
        );

        // test override existing filter value
        $accessor->set('group[path]', null);
        self::assertCount(0, $accessor->get('group[path]'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('group'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingFilterValueViaRemoveMethod(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?prm1=val1'));

        self::assertEquals([$this->getFilterValue('prm1', 'val1', 'eq', 'prm1')], $accessor->get('prm1'));

        // test remove existing filter value by key
        $accessor->remove('prm1');
        self::assertCount(0, $accessor->get('prm1'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('prm1'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testRemoveExistingGroupedFilterValueViaRemoveMethod(): void
    {
        $accessor = $this->getRestFilterValueAccessor(Request::create('http://test.com?group[path]=val1'));

        self::assertEquals(
            [$this->getFilterValue('path', 'val1', 'eq', 'group[path]')],
            $accessor->get('group[path]')
        );

        // test remove existing filter value by key
        $accessor->remove('group[path]');
        self::assertCount(0, $accessor->get('group[path]'));
        self::assertCount(0, $accessor->getAll(), 'getAll');
        self::assertCount(0, $accessor->getGroup('group'), 'getGroup');

        self::assertEquals(
            '',
            $accessor->getQueryString()
        );
    }

    public function testDefaultGroup(): void
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

        self::assertEquals([$filter1], $accessor->get('filter1'));
        self::assertCount(0, $accessor->get('filter[filter1]'));
        self::assertEquals([$filter2], $accessor->get('filter2'));
        self::assertEquals([$filter2], $accessor->get('filter[filter2]'));

        self::assertEquals(
            'filter1=val1'
            . '&filter%5Bfilter2%5D=val2',
            $accessor->getQueryString()
        );
    }
}
