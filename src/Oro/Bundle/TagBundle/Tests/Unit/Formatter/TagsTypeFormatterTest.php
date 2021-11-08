<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\TagBundle\Formatter\TagsTypeFormatter;

class TagsTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagsTypeFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->formatter = new TagsTypeFormatter();
    }

    /**
     * @dataProvider formatTypeDataProvider
     */
    public function testFormatType(array $value, string $type, ?string $exception, string $expected)
    {
        if ($exception) {
            $this->expectException($exception);
        }

        $this->assertEquals($expected, $this->formatter->formatType($value, $type));
    }

    public function formatTypeDataProvider(): array
    {
        $value    = [
            ['name' => 1],
            ['name' => 2],
            ['name' => 3]
        ];
        $expected = '1,2,3';

        return [
            'default'                => [
                'value'     => $value,
                'type'      => 'tags',
                'exception' => null,
                'expected'  => $expected
            ],
            'Invalid type exception' => [
                'value'     => $value,
                'type'      => 'not_exists_type',
                'exception' => InvalidArgumentException::class,
                'expected'  => $expected
            ]
        ];
    }
}
