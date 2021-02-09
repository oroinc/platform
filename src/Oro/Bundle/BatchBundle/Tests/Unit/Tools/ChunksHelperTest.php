<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Tools;

use Oro\Bundle\BatchBundle\Tools\ChunksHelper;

class ChunksHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider chunksDataProvider
     *
     * @param array $data
     * @param array $expectedChunks
     */
    public function testSplitInChunks(array $data, array $expectedChunks): void
    {
        foreach (ChunksHelper::splitInChunks($data, 2) as $chunk) {
            $this->assertEquals(array_shift($expectedChunks), $chunk);
        }
    }

    /**
     * @return array
     */
    public function chunksDataProvider(): array
    {
        return [
            'empty data set' => [
                'data' => [],
                'expectedChunks' => [],
            ],
            'even data set' => [
                'data' => [
                    ['sample_key' => 'sample_value1'],
                    ['sample_key' => 'sample_value2'],
                    ['sample_key' => 'sample_value3'],
                    ['sample_key' => 'sample_value4'],
                ],
                'expectedChunks' => [
                    [
                        ['sample_key' => 'sample_value1'],
                        ['sample_key' => 'sample_value2'],
                    ],
                    [
                        ['sample_key' => 'sample_value3'],
                        ['sample_key' => 'sample_value4'],
                    ],
                ],
            ],
            'not even data set' => [
                'data' => [
                    ['sample_key' => 'sample_value1'],
                    ['sample_key' => 'sample_value2'],
                    ['sample_key' => 'sample_value3'],
                ],
                'expectedChunks' => [
                    [
                        ['sample_key' => 'sample_value1'],
                        ['sample_key' => 'sample_value2'],
                    ],
                    [
                        ['sample_key' => 'sample_value3'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider chunksDataProvider
     *
     * @param array $data
     * @param array $expectedChunks
     */
    public function testSplitInChunksByColumn(array $data, array $expectedChunks): void
    {
        foreach (ChunksHelper::splitInChunksByColumn($data, 2, 'sample_key') as $chunk) {
            $this->assertEquals(array_column(array_shift($expectedChunks), 'sample_key'), $chunk);
        }
    }
}
