<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Processor\MultiTargetSearch;

use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetAggregatedDataJoiner;
use PHPUnit\Framework\TestCase;

class MultiTargetAggregatedDataJoinerTest extends TestCase
{
    /**
     * @dataProvider joinDataProvider
     */
    public function testJoin(array $toJoinAggregation, array $aggregatedData, array $expectedAggregatedData): void
    {
        $aggregatedDataJoiner = new MultiTargetAggregatedDataJoiner();
        $result = $aggregatedDataJoiner->join($aggregatedData, $toJoinAggregation);
        self::assertEquals($expectedAggregatedData, $result);
    }

    public static function joinDataProvider(): array
    {
        return [
            'count (int)' => [
                ['f1Count' => ['count' => ['integer' => ['f1Count_p1', 'f1Count_p2', 'f1Count_p3', 'f1Count_p4']]]],
                [
                    'f1Count_p1' => [1 => 1, 2 => 1],
                    'f1Count_p2' => [1 => 3, 2 => 1],
                    'f1Count_p3' => [2 => 1, 3 => 1],
                    'f2Count' => [1 => 2]
                ],
                [
                    'f1Count' => [1 => 4, 2 => 3, 3 => 1],
                    'f2Count' => [1 => 2]
                ]
            ],
            'count (string)' => [
                ['f1Count' => ['count' => ['string' => ['f1Count_p1', 'f1Count_p2', 'f1Count_p3', 'f1Count_p4']]]],
                [
                    'f1Count_p1' => ['i1' => 1, 'i2' => 1],
                    'f1Count_p2' => ['i1' => 3, 'i2' => 1],
                    'f1Count_p3' => ['i2' => 1, 'i3' => 1],
                    'f2Count' => ['i1' => 2]
                ],
                [
                    'f1Count' => ['i1' => 4, 'i2' => 3, 'i3' => 1],
                    'f2Count' => ['i1' => 2]
                ]
            ],
            'sum' => [
                ['f1Sum' => ['sum' => ['integer' => ['f1Sum_p1', 'f1Sum_p2', 'f1Sum_p3', 'f1Count_p4']]]],
                ['f1Sum_p1' => 1, 'f1Sum_p2' => 3, 'f1Sum_p3' => 2, 'f2Sum' => 2],
                ['f1Sum' => 6, 'f2Sum' => 2]
            ],
            'avg' => [
                ['f1Avg' => ['avg' => ['integer' => ['f1Avg_p1', 'f1Avg_p2', 'f1Avg_p3', 'f1Count_p4']]]],
                ['f1Avg_p1' => 1, 'f1Avg_p2' => 3, 'f1Avg_p3' => 2, 'f2Avg' => 2],
                ['f1Avg' => 2, 'f2Avg' => 2]
            ],
            'min' => [
                ['f1Min' => ['min' => ['integer' => ['f1Min_p1', 'f1Min_p2', 'f1Min_p3', 'f1Count_p4']]]],
                ['f1Min_p1' => 1, 'f1Min_p2' => 3, 'f1Min_p3' => 2, 'f2Min' => 2],
                ['f1Min' => 1, 'f2Min' => 2]
            ],
            'max' => [
                ['f1Max' => ['max' => ['integer' => ['f1Max_p1', 'f1Max_p2', 'f1Max_p3', 'f1Count_p4']]]],
                ['f1Max_p1' => 1, 'f1Max_p2' => 3, 'f1Max_p3' => 2, 'f2Max' => 2],
                ['f1Max' => 3, 'f2Max' => 2]
            ],
            'min (datetime) (timestamp)' => [
                ['f1Min' => ['min' => ['datetime' => ['f1Min_p1', 'f1Min_p2', 'f1Min_p3', 'f1Count_p4']]]],
                ['f1Min_p1' => 1000000001, 'f1Min_p2' => 1000000003, 'f1Min_p3' => 1000000002, 'f2Min' => 1000000002],
                ['f1Min' => 1000000001, 'f2Min' => 1000000002]
            ],
            'max (datetime) (timestamp)' => [
                ['f1Max' => ['max' => ['datetime' => ['f1Max_p1', 'f1Max_p2', 'f1Max_p3', 'f1Count_p4']]]],
                ['f1Max_p1' => 1000000001, 'f1Max_p2' => 1000000003, 'f1Max_p3' => 1000000002, 'f2Max' => 1000000002],
                ['f1Max' => 1000000003, 'f2Max' => 1000000002]
            ],
            'min (datetime) (string)' => [
                ['f1Min' => ['min' => ['datetime' => ['f1Min_p1', 'f1Min_p2', 'f1Min_p3', 'f1Count_p4']]]],
                [
                    'f1Min_p1' => 'September 9, 2001 1:46:41',
                    'f1Min_p2' => 'November 9, 2001 1:46:41',
                    'f1Min_p3' => 'October 9, 2001 1:46:41',
                    'f2Min' => 'October 9, 2001 1:46:41'
                ],
                [
                    'f1Min' => 'September 9, 2001 1:46:41',
                    'f2Min' => 'October 9, 2001 1:46:41'
                ]
            ],
            'max (datetime) (string)' => [
                ['f1Max' => ['max' => ['datetime' => ['f1Max_p1', 'f1Max_p2', 'f1Max_p3', 'f1Count_p4']]]],
                [
                    'f1Max_p1' => 'September 9, 2001 1:46:41',
                    'f1Max_p2' => 'November 9, 2001 1:46:41',
                    'f1Max_p3' => 'October 9, 2001 1:46:41',
                    'f2Max' => 'October 9, 2001 1:46:41'
                ],
                [
                    'f1Max' => 'November 9, 2001 1:46:41',
                    'f2Max' => 'October 9, 2001 1:46:41'
                ]
            ],
        ];
    }
}
