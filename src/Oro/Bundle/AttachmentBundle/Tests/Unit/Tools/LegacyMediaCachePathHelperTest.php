<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\LegacyMediaCachePathHelper;
use PHPUnit\Framework\TestCase;

class LegacyMediaCachePathHelperTest extends TestCase
{
    /**
     * @dataProvider legacyPathDataProvider
     */
    public function testGetLegacyPath(string $path, string $expectedLegacyPath): void
    {
        self::assertSame($expectedLegacyPath, LegacyMediaCachePathHelper::getLegacyPath($path));
    }

    public function legacyPathDataProvider(): array
    {
        return [
            'ascii path is not changed' => [
                'path' => '/attachment/filter/avatar_med/1/file.jpg',
                'expectedLegacyPath' => '/attachment/filter/avatar_med/1/file.jpg',
            ],
            'characters left unencoded by the url generator are not encoded' => [
                'path' => '/attachment/filter/avatar_med/1/a@b:c;d,e=f+g!h*i|j.jpg',
                'expectedLegacyPath' => '/attachment/filter/avatar_med/1/a@b:c;d,e=f+g!h*i|j.jpg',
            ],
            'latin accented characters are encoded' => [
                'path' => '/attachment/filter/avatar_med/1/café.jpg',
                'expectedLegacyPath' => '/attachment/filter/avatar_med/1/caf%C3%A9.jpg',
            ],
            'cyrillic characters are encoded' => [
                'path' => 'attachment/filter/avatar_med/1/файл.jpg',
                'expectedLegacyPath' => 'attachment/filter/avatar_med/1/%D1%84%D0%B0%D0%B9%D0%BB.jpg',
            ],
            'space is encoded' => [
                'path' => '/attachment/filter/avatar_med/1/my photo.jpg',
                'expectedLegacyPath' => '/attachment/filter/avatar_med/1/my%20photo.jpg',
            ],
        ];
    }

    /**
     * @dataProvider roundTripDataProvider
     */
    public function testGetLegacyPathIsReverseOfDecoding(string $encodedPath): void
    {
        self::assertSame($encodedPath, LegacyMediaCachePathHelper::getLegacyPath(rawurldecode($encodedPath)));
    }

    public function roundTripDataProvider(): array
    {
        return [
            ['/attachment/filter/avatar_med/1/file.jpg'],
            ['/attachment/filter/avatar_med/1/caf%C3%A9.jpg'],
            ['/attachment/resize/1/10/20/%D1%84%D0%BE%D1%82%D0%BE-%D0%BA%D0%B0%D1%84%D0%B5.jpg'],
            ['/attachment/filter/avatar_med/1/a+b|c.jpg.webp'],
        ];
    }
}
