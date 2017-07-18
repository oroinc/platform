<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification;

use Oro\Bundle\TestFrameworkBundle\Behat\Specification\SpecificationDivider;

class SpecificationDividerTest extends \PHPUnit_Framework_TestCase
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
        $suiteDivider = new SpecificationDivider();
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
                    self::BASE_NAME.'#0' => [1, 2, 3],
                    self::BASE_NAME.'#1' => [4, 5, 6],
                    self::BASE_NAME.'#2' => [10, 7],
                    self::BASE_NAME.'#3' => [8, 9]
                ],
            ],
            [
                'array' => [1, 2, 3, 4],
                'Suite divider' => 3,
                'Expected result' => [
                    self::BASE_NAME.'#0' => [4, 1],
                    self::BASE_NAME.'#1' => [2, 3],
                ],
            ],
            [
                'array' => [1, 2, 3, 4],
                'Suite divider' => 1,
                'Expected result' => [
                    self::BASE_NAME.'#0' => [1],
                    self::BASE_NAME.'#1' => [2],
                    self::BASE_NAME.'#2' => [3],
                    self::BASE_NAME.'#3' => [4],
                ]
            ],
            [
                'array' => [1, 2, 3, 4, 5],
                'Suite divider' => 7,
                'Expected result' => [
                    self::BASE_NAME.'#0' => [1, 2, 3, 4, 5]
                ]
            ],
        ];
    }
}
