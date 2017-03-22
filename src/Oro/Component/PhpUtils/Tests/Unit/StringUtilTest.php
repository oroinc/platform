<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\StringUtil;

class StringUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider squashChunksDataProvider
     */
    public function testSquashChunks(array $strings, $maxChunkSize, array $expected)
    {
        $this->assertEquals($expected, StringUtil::squashChunks($strings, $maxChunkSize));
    }

    public function squashChunksDataProvider()
    {
        return [
            'chunk size is lower than shortest string' => [
                [
                    'first',
                    'second',
                    'third',
                ],
                4,
                [
                    'first',
                    'second',
                    'third',
                ],
            ],
            'chunk size is high as first two strings combined' => [
                [
                    'first',
                    'second',
                    'third',
                ],
                11,
                [
                    'firstsecond',
                    'third',
                ],
            ],
            'chunk size is bigger than all strings combined' => [
                [
                    'first',
                    'second',
                    'third',
                ],
                999999,
                [
                    'firstsecondthird',
                ],
            ],
            'chunk size is not specified' => [
                [
                    'first',
                    'second',
                    'third',
                ],
                null,
                [
                    'firstsecondthird',
                ],
            ],
        ];
    }
}
