<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Tools;

use Oro\Bundle\BatchBundle\Tools\ChunkCallbackHelper;

class ChunkCallbackHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processInChunksDataProvider
     *
     * @param array $dataSet
     * @param array $expectedChunks
     * @param int $expectedCount
     * @param int $chunkSize
     */
    public function testProcessInChunks(array $dataSet, array $expectedChunks, int $expectedCount, int $chunkSize): void
    {
        $count = ChunkCallbackHelper::processInChunks(
            $dataSet,
            static function (&$chunk, $value, $key) {
                $chunk[$key] = $value;
            },
            function ($chunk) use (&$expectedChunks) {
                $expectedChunk = array_shift($expectedChunks);
                $this->assertEquals($expectedChunk, $chunk);
            },
            $chunkSize
        );

        $this->assertEquals($count, $expectedCount);
    }

    /**
     * @return array
     */
    public function processInChunksDataProvider(): array
    {
        return [
            'empty data set' => [
                'dataSet' => [],
                'expectedChunks' => [],
                'expectedCount' => 0,
                'chunkSize' => 10,
            ],
            'even data set' => [
                'dataSet' => [
                    'sample_key1' => 'sample_value1',
                    'sample_key2' => 'sample_value2',
                    'sample_key3' => 'sample_value3',
                    'sample_key4' => 'sample_value4',
                ],
                'expectedChunks' => [
                    [
                        'sample_key1' => 'sample_value1',
                        'sample_key2' => 'sample_value2',
                    ],
                    [
                        'sample_key3' => 'sample_value3',
                        'sample_key4' => 'sample_value4',
                    ],
                ],
                'expectedCount' => 4,
                'chunkSize' => 2,
            ],
            'not even data set' => [
                'dataSet' => [
                    'sample_key1' => 'sample_value1',
                    'sample_key2' => 'sample_value2',
                    'sample_key3' => 'sample_value3',
                    'sample_key4' => 'sample_value4',
                ],
                'expectedChunks' => [
                    [
                        'sample_key1' => 'sample_value1',
                        'sample_key2' => 'sample_value2',
                        'sample_key3' => 'sample_value3',
                    ],
                    [
                        'sample_key4' => 'sample_value4',
                    ],
                ],
                'expectedCount' => 4,
                'chunkSize' => 3,
            ],
        ];
    }
}
