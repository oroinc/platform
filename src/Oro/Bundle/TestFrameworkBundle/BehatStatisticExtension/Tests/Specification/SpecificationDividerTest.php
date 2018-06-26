<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Specification;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\SpecificationCountDivider;

class SpecificationDividerTest extends \PHPUnit\Framework\TestCase
{
    const BASE_NAME = 'SuiteStub';

    /**
     * @dataProvider divideSuiteProvider
     *
     * @param int $featureCount
     * @param int $divider
     * @param array $expectedResult
     */
    public function testDivide($array, $divider, array $expectedResult)
    {
        $suiteDivider = new SpecificationCountDivider();
        $actualResult = $suiteDivider->divide(self::BASE_NAME, $array, $divider);

        $this->assertTrue(is_array($actualResult));
        $this->assertCount(count($expectedResult), $actualResult);
        $this->assertSame($expectedResult, $actualResult);
    }

    public function divideSuiteProvider()
    {
        return [
            [
                'array' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'Suite divider' => 3,
                'Expected result' => [
                    self::BASE_NAME.'_0' => [1, 2, 3],
                    self::BASE_NAME.'_1' => [4, 5, 6],
                    self::BASE_NAME.'_2' => [10, 7],
                    self::BASE_NAME.'_3' => [8, 9]
                ],
            ],
            [
                'array' => [1, 2, 3, 4],
                'Suite divider' => 3,
                'Expected result' => [
                    self::BASE_NAME.'_0' => [4, 1],
                    self::BASE_NAME.'_1' => [2, 3],
                ],
            ],
            [
                'array' => [1, 2, 3, 4],
                'Suite divider' => 1,
                'Expected result' => [
                    self::BASE_NAME.'_0' => [1],
                    self::BASE_NAME.'_1' => [2],
                    self::BASE_NAME.'_2' => [3],
                    self::BASE_NAME.'_3' => [4],
                ]
            ],
            [
                'array' => [1, 2, 3, 4, 5],
                'Suite divider' => 7,
                'Expected result' => [
                    self::BASE_NAME.'_0' => [1, 2, 3, 4, 5]
                ]
            ],
        ];
    }
}
