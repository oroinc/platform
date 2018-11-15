<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\QueryStringUtil;

class QueryStringUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider buildQueryStringDataProvider
     */
    public function testBuildQueryString($parameters, $expectedQueryString)
    {
        self::assertEquals(
            $expectedQueryString,
            QueryStringUtil::buildQueryString($parameters)
        );
    }

    public function buildQueryStringDataProvider()
    {
        return [
            'empty'            => [
                'parameters'            => [],
                'expected_query_string' => ''
            ],
            'one param'        => [
                'parameters'            => ['prm1' => 'val1'],
                'expected_query_string' => 'prm1=val1'
            ],
            'several param'    => [
                'parameters'            => ['prm1' => 'val1', 'prm2' => '', 'prm3' => 'val3'],
                'expected_query_string' => 'prm1=val1&prm2=&prm3=val3'
            ],
            '[] in param name' => [
                'parameters'            => ['filter[prm1]' => 'val1', 'filter[prm2][prmm21]' => 'val2'],
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D%5Bprmm21%5D=val2'
            ]
        ];
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the parameter "prm1", given "NULL".
     */
    public function testBuildQueryStringWithNullParameterValue()
    {
        QueryStringUtil::buildQueryString(['prm1' => null]);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the parameter "prm1", given "integer".
     */
    public function testBuildQueryStringWithNotStringScalarParameterValue()
    {
        QueryStringUtil::buildQueryString(['prm1' => 0]);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the parameter "prm1", given "array".
     */
    public function testBuildQueryStringWithArrayParameterValue()
    {
        QueryStringUtil::buildQueryString(['prm1' => []]);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Expected string value for the parameter "prm1", given "stdClass".
     */
    public function testBuildQueryStringWithObjectParameterValue()
    {
        QueryStringUtil::buildQueryString(['prm1' => new \stdClass()]);
    }

    /**
     * @dataProvider addQueryStringDataProvider
     */
    public function testAddQueryString($url, $queryString, $expectedUrl)
    {
        self::assertEquals(
            $expectedUrl,
            QueryStringUtil::addQueryString($url, $queryString)
        );
    }

    public function addQueryStringDataProvider()
    {
        return [
            'empty query string' => [
                'url'          => 'http://test.com',
                'query_string' => '',
                'expected_url' => 'http://test.com'
            ],
            'url without params' => [
                'url'          => 'http://test.com',
                'query_string' => 'prm1=val1',
                'expected_url' => 'http://test.com?prm1=val1'
            ],
            'url with params'    => [
                'url'          => 'http://test.com?prm1=val1',
                'query_string' => 'prm2=val2',
                'expected_url' => 'http://test.com?prm1=val1&prm2=val2'
            ]
        ];
    }

    /**
     * @dataProvider addParameterDataProvider
     */
    public function testAddParameter($queryString, $parameterName, $parameterValue, $expectedQueryString)
    {
        self::assertEquals(
            $expectedQueryString,
            QueryStringUtil::addParameter($queryString, $parameterName, $parameterValue)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function addParameterDataProvider()
    {
        return [
            'empty query string'                                                     => [
                'query_string'          => '',
                'parameter_name'        => 'prm1',
                'parameter_value'       => 'val1',
                'expected_query_string' => 'prm1=val1'
            ],
            'new param'                                                              => [
                'query_string'          => 'prm1=val1',
                'parameter_name'        => 'prm2',
                'parameter_value'       => 'val2',
                'expected_query_string' => 'prm1=val1&prm2=val2'
            ],
            'override first param'                                                   => [
                'query_string'          => 'prm1=val1&prm2=val2&prm3=val3',
                'parameter_name'        => 'prm1',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'prm1=newVal&prm2=val2&prm3=val3'
            ],
            'override middle param'                                                  => [
                'query_string'          => 'prm1=val1&prm2=val2&prm3=val3',
                'parameter_name'        => 'prm2',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'prm1=val1&prm2=newVal&prm3=val3'
            ],
            'override last param'                                                    => [
                'query_string'          => 'prm1=val1&prm2=val2&prm3=val3',
                'parameter_name'        => 'prm3',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'prm1=val1&prm2=val2&prm3=newVal'
            ],
            'override first param with [] in name'                                   => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter[prm1]',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=newVal&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'override middle param with [] in name'                                  => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter[prm2]',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=newVal&filter%5Bprm3%5D=val3'
            ],
            'override last param with [] in name'                                    => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter[prm3]',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=newVal'
            ],
            'override first param with encoded [] in param name'                     => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter%5Bprm1%5D',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=newVal&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'override middle param with encoded [] in param name'                    => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter%5Bprm2%5D',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=newVal&filter%5Bprm3%5D=val3'
            ],
            'override last param with encoded [] in param name'                      => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter%5Bprm3%5D',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=newVal'
            ],
            'override first param with [] in name and encoded query string'          => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter[prm1]',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=newVal&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'override middle param with [] in name and encoded query string'         => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter[prm2]',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=newVal&filter%5Bprm3%5D=val3'
            ],
            'override last param with [] in name and encoded query string'           => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter[prm3]',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=newVal'
            ],
            'override first param with encoded [] in name and encoded query string'  => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter%5Bprm1%5D',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=newVal&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'override middle param with encoded [] in name and encoded query string' => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter%5Bprm2%5D',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=newVal&filter%5Bprm3%5D=val3'
            ],
            'override last param with encoded [] in name and encoded query string'   => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter%5Bprm3%5D',
                'parameter_value'       => 'newVal',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=newVal'
            ]
        ];
    }

    /**
     * @dataProvider removeParameterDataProvider
     */
    public function testRemoveParameter($queryString, $parameterName, $expectedQueryString)
    {
        self::assertEquals(
            $expectedQueryString,
            QueryStringUtil::removeParameter($queryString, $parameterName)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function removeParameterDataProvider()
    {
        return [
            'empty query string'                                                   => [
                'query_string'          => '',
                'parameter_name'        => 'prm1',
                'expected_query_string' => ''
            ],
            'remove not existing param'                                            => [
                'query_string'          => 'prm1=val1',
                'parameter_name'        => 'prm2',
                'expected_query_string' => 'prm1=val1'
            ],
            'remove first param'                                                   => [
                'query_string'          => 'prm1=val1&prm2=val2&prm3=val3',
                'parameter_name'        => 'prm1',
                'expected_query_string' => 'prm2=val2&prm3=val3'
            ],
            'remove middle param'                                                  => [
                'query_string'          => 'prm1=val1&prm2=val2&prm3=val3',
                'parameter_name'        => 'prm2',
                'expected_query_string' => 'prm1=val1&prm3=val3'
            ],
            'remove last param'                                                    => [
                'query_string'          => 'prm1=val1&prm2=val2&prm3=val3',
                'parameter_name'        => 'prm3',
                'expected_query_string' => 'prm1=val1&prm2=val2'
            ],
            'remove first param with [] in name'                                   => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter[prm1]',
                'expected_query_string' => 'filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'remove middle param with [] in name'                                  => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter[prm2]',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm3%5D=val3'
            ],
            'remove last param with [] in name'                                    => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter[prm3]',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2'
            ],
            'remove first param with encoded [] in param name'                     => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter%5Bprm1%5D',
                'expected_query_string' => 'filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'remove middle param with encoded [] in param name'                    => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter%5Bprm2%5D',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm3%5D=val3'
            ],
            'remove last param with encoded [] in param name'                      => [
                'query_string'          => 'filter[prm1]=val1&filter[prm2]=val2&filter[prm3]=val3',
                'parameter_name'        => 'filter%5Bprm3%5D',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2'
            ],
            'remove first param with [] in name and encoded query string'          => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter[prm1]',
                'expected_query_string' => 'filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'remove middle param with [] in name and encoded query string'         => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter[prm2]',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm3%5D=val3'
            ],
            'remove last param with [] in name and encoded query string'           => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter[prm3]',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2'
            ],
            'remove first param with encoded [] in name and encoded query string'  => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter%5Bprm1%5D',
                'expected_query_string' => 'filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3'
            ],
            'remove middle param with encoded [] in name and encoded query string' => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter%5Bprm2%5D',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm3%5D=val3'
            ],
            'remove last param with encoded [] in name and encoded query string'   => [
                'query_string'          => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2&filter%5Bprm3%5D=val3',
                'parameter_name'        => 'filter%5Bprm3%5D',
                'expected_query_string' => 'filter%5Bprm1%5D=val1&filter%5Bprm2%5D=val2'
            ],
            'remove last param in group'                                           => [
                'query_string'          => 'page%5Bnumber%5D=1&filter%5Bprm1%5D=val1',
                'parameter_name'        => 'page[number]',
                'expected_query_string' => 'filter%5Bprm1%5D=val1'
            ],
            'remove not existing param in group'                                   => [
                'query_string'          => 'page%5Bnumber%5D=1&filter%5Bprm1%5D=val1',
                'parameter_name'        => 'page[size]',
                'expected_query_string' => 'page%5Bnumber%5D=1&filter%5Bprm1%5D=val1'
            ]
        ];
    }
}
