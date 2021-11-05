<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Cache\Metadata\DefaultCacheMetadataProvider;
use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;

class DefaultCacheMetadataProviderTest extends TestCase
{
    /**
     * @var DefaultCacheMetadataProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new DefaultCacheMetadataProvider();
    }

    /**
     * @dataProvider getCacheMetadataProvider
     * @param mixed $cacheConfig
     * @param array $expected
     */
    public function testGetCacheMetadata($cacheConfig, array $expected): void
    {
        $blockView = new BlockView();
        $blockView->vars['cache'] = $cacheConfig;
        $context = new LayoutContext();
        $result = $this->provider->getCacheMetadata($blockView, $context);

        $this->assertInstanceOf(LayoutCacheMetadata::class, $result);
        $this->assertEquals($expected['tags'], $result->getTags());
        $this->assertEquals($expected['varyBy'], $result->getVaryBy());
        $this->assertEquals($expected['maxAge'], $result->getMaxAge());
    }

    public function getCacheMetadataProvider(): array
    {
        return [
            [
                'cache' => true,
                'expected' => [
                    'tags' => [],
                    'varyBy' => [],
                    'maxAge' => null,
                ],
            ],
            [
                'cache' => [
                    'maxAge' => 900,
                ],
                'expected' => [
                    'tags' => [],
                    'varyBy' => [],
                    'maxAge' => 900,
                ],
            ],
            [
                'cache' => [
                    'maxAge' => null,
                ],
                'expected' => [
                    'tags' => [],
                    'varyBy' => [],
                    'maxAge' => null,
                ],
            ],
            [
                'cache' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                ],
                'expected' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => null,
                ],
            ],
            [
                'cache' => [
                    'if' => true,
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 900,
                ],
                'expected' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 900,
                ],
            ],
            [
                'cache' => [
                    'maxAge' => 0,
                ],
                'expected' => [
                    'tags' => [],
                    'varyBy' => [],
                    'maxAge' => 0,
                ],
            ],
            [
                'cache' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 0,
                ],
                'expected' => [
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 0,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getCacheMetadataNullResultProvider
     * @param bool|null|array $cacheConfig
     */
    public function testGetCacheMetadataNullResult($cacheConfig)
    {
        $blockView = new BlockView();
        $blockView->vars['cache'] = $cacheConfig;
        $context = new LayoutContext();
        $result = $this->provider->getCacheMetadata($blockView, $context);
        $this->assertNull($result);
    }

    public function getCacheMetadataNullResultProvider(): array
    {
        return [
            ['cache' => false],
            ['cache' => null],
            [
                'cache' => [
                    'if' => false,
                    'tags' => ['tag1', 'tag2'],
                    'varyBy' => ['entity1', 'entity2'],
                    'maxAge' => 900,
                ],
            ],
        ];
    }
}
