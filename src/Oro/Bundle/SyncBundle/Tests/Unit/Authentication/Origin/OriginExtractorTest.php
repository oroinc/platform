<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginExtractor;
use PHPUnit\Framework\TestCase;

class OriginExtractorTest extends TestCase
{
    private OriginExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->extractor = new OriginExtractor();
    }

    /**
     * @dataProvider urlDataProvider
     */
    public function testFromUrl(?string $url, ?string $expectedOrigin): void
    {
        self::assertEquals($expectedOrigin, $this->extractor->fromUrl($url));
    }

    public function urlDataProvider(): array
    {
        return [
            ['example', 'example'],
            ['http://example', 'example'],
            ['http://example.com', 'example.com'],
            ['example.com', 'example.com'],
            ['http://example.com/some/path', 'example.com'],
            ['example.com/some/path', 'example.com'],
            ['http://www.example', 'www.example'],
            ['http://www.example.com', 'www.example.com'],
            ['www.example.com', 'www.example.com'],
            ['//www.example.com', 'www.example.com'],
            ['  www.example.com  ', 'www.example.com'],
            ['  //www.example.com/some/path  ', 'www.example.com'],
            ['http://www.example.com/some/path', 'www.example.com'],
            ['www.example.com/some/path', 'www.example.com'],
            ['http://', null],
            ['', null],
            [null, null],
        ];
    }
}
