<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\MediaTypeHeaderUtil;

class MediaTypeHeaderUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider parseMediaTypeDataProvider
     */
    public function testParseMediaType(
        string $headerValue,
        string $expectedMediaType,
        array $expectedMediaTypeParameters
    ): void {
        [$mediaType, $mediaTypeParameters] = MediaTypeHeaderUtil::parseMediaType($headerValue);
        self::assertSame($expectedMediaType, $mediaType);
        self::assertSame($expectedMediaTypeParameters, $mediaTypeParameters);
    }

    public function parseMediaTypeDataProvider(): array
    {
        return [
            ['', '', []],
            ['application/vnd.api+json', 'application/vnd.api+json', []],
            [
                'application/vnd.api+json; profile="https://test.com/profile"',
                'application/vnd.api+json',
                ['profile' => 'https://test.com/profile']
            ],
            [
                'application/vnd.api+json;profile="https://test.com/profile1";profile="https://test.com/profile2"',
                'application/vnd.api+json',
                ['profile' => ['https://test.com/profile1', 'https://test.com/profile2']]
            ],
            [
                'application/vnd.api+json; profile="https://test.com/profile1"; profile="https://test.com/profile2"'
                . '; profile="https://test.com/profile3"',
                'application/vnd.api+json',
                ['profile' => ['https://test.com/profile1', 'https://test.com/profile2', 'https://test.com/profile3']]
            ],
            [
                'application/vnd.api+json; profile="https://test.com/profile"; ext="https://test.com/ext"',
                'application/vnd.api+json',
                ['profile' => 'https://test.com/profile', 'ext' => 'https://test.com/ext']
            ],
        ];
    }
}
