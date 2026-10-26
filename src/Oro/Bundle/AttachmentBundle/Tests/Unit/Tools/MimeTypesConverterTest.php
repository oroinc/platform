<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use PHPUnit\Framework\TestCase;

final class MimeTypesConverterTest extends TestCase
{
    /**
     * @dataProvider mediaTypeDataProvider
     */
    public function testConvertToMediaType(string $mimeType, string $expected): void
    {
        self::assertSame($expected, MimeTypesConverter::convertToMediaType($mimeType));
    }

    public function mediaTypeDataProvider(): array
    {
        return [
            'plain media type' => ['image/png', 'image/png'],
            'upper case' => ['IMAGE/PNG', 'image/png'],
            'surrounding spaces' => [' text/plain ', 'text/plain'],
            'parameter and an extra media type' => ['text/plain; charset=utf-8,text/html', 'text/plain'],
            'list of media types' => ['text/plain,text/html', 'text/plain,text/html'],
            'empty' => ['', ''],
        ];
    }

    /**
     * @dataProvider isMediaTypeDataProvider
     */
    public function testIsMediaType(string $value, bool $expected): void
    {
        self::assertSame($expected, MimeTypesConverter::isMediaType($value));
    }

    public function isMediaTypeDataProvider(): array
    {
        return [
            'media type' => ['image/png', true],
            'media type with suffix' => ['image/svg+xml', true],
            'vendor media type' => ['application/vnd.ms-excel', true],
            'upper case' => ['IMAGE/PNG', false],
            'without subtype' => ['image', false],
            'with parameter' => ['text/plain; charset=utf-8', false],
            'list of media types' => ['text/plain,text/html', false],
            'control characters' => ["text/plain\r\nx-test: 1", false],
            'trailing line break' => ["image/png\n", false],
            'empty' => ['', false],
        ];
    }
}
