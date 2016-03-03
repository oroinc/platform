<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\QueryUtil;

class QueryUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider optimizeIntValuesProvider
     */
    public function testOptimizeIntValues(array $intValues, array $expectedResult)
    {
        $this->assertEquals($expectedResult, QueryUtil::optimizeIntValues($intValues));
    }

    public function optimizeIntValuesProvider()
    {
        return [
            [
                [1, 2, 3, 4, 5, 10, 101, 200, 201, 300],
                [
                    QueryUtil::IN => [10, 101, 300],
                    QueryUtil::IN_BETWEEN => [[1, 5], [200, 201]],
                ]
            ]
        ];
    }

    public function testGenerateParameterNameTest()
    {
        $generatedNames = [];
        for ($i = 0; $i < 100; $i++) {
            $generatedNames[] = QueryUtil::generateParameterName('pref');
        }

        $this->assertCount(100, $generatedNames);
        $this->assertCount(100, array_unique($generatedNames));
        for ($i = 0; $i < 100; $i++) {
            $this->assertStringStartsWith('pref', $generatedNames[$i]);
        }
    }
}
