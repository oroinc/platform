<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginExtractor;

class OriginExtractorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OriginExtractor
     */
    private $extractor;

    protected function setUp()
    {
        $this->extractor = new OriginExtractor();
    }

    /**
     * @dataProvider urlDataProvider
     *
     * @param string|null $url
     * @param string|null $expectedOrigin
     */
    public function testFromUrl($url, $expectedOrigin)
    {
        self::assertEquals($expectedOrigin, $this->extractor->fromUrl($url));
    }

    /**
     * @return array
     */
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
