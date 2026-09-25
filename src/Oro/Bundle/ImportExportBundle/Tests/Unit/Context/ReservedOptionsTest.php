<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Context;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ReservedOptions;
use PHPUnit\Framework\TestCase;

class ReservedOptionsTest extends TestCase
{
    /**
     * @dataProvider detectDataProvider
     */
    public function testDetect(array $options, array $expected): void
    {
        self::assertSame($expected, ReservedOptions::detect($options));
    }

    public function detectDataProvider(): array
    {
        return [
            'no options' => [
                'options' => [],
                'expected' => [],
            ],
            'business keys only' => [
                'options' => ['price_list_id' => 1, 'writer_skip_clear' => true, 'categoryId' => 5],
                'expected' => [],
            ],
            'file path' => [
                'options' => [Context::OPTION_FILE_PATH => '/tmp/test.csv'],
                'expected' => [Context::OPTION_FILE_PATH],
            ],
            'writer options' => [
                'options' => [
                    Context::OPTION_DELIMITER => ';',
                    Context::OPTION_ENCLOSURE => '|',
                    Context::OPTION_ESCAPE => '\\',
                    Context::OPTION_HEADER => ['a'],
                ],
                'expected' => [
                    Context::OPTION_DELIMITER,
                    Context::OPTION_ENCLOSURE,
                    Context::OPTION_ESCAPE,
                    Context::OPTION_HEADER,
                ],
            ],
            'keys the server owns but a caller may still send' => [
                'options' => [Context::OPTION_BATCH_SIZE => 100, Context::OPTION_FIRST_LINE_IS_HEADER => false],
                'expected' => [],
            ],
        ];
    }
}
