<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Formatter;

use Oro\Bundle\TagBundle\Formatter\TagsTypeFormatter;

class TagsTypeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagsTypeFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = new TagsTypeFormatter();
    }

    /**
     * @dataProvider formatTypeDataProvider
     *
     * @param $value
     * @param $type
     * @param $exception
     * @param $expected
     */
    public function testFormatType($value, $type, $exception, $expected)
    {
        if ($exception) {
            $this->expectException($exception);
        }

        $val = $this->formatter->formatType($value, $type);

        $this->assertEquals($val, $expected);
    }

    public function formatTypeDataProvider()
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
                'exception' => false,
                'expected'  => $expected
            ],
            'Invalid type exception' => [
                'value'     => $value,
                'type'      => 'not_exists_type',
                'exception' => 'Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException',
                'expected'  => $expected
            ]
        ];
    }
}
