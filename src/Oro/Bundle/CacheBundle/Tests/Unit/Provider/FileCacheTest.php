<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\CacheBundle\Provider\PhpFileCache;
use Oro\Bundle\CacheBundle\Provider\SyncCacheInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;

class FileCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /**
     * @dataProvider getFilenameProvider
     */
    public function testGetFilename(string $cacheClass, string $id, ?string $namespace, string $expectedFileName)
    {
        $directory = $this->getTempDir('file_cache');

        $cache = $this->getMockBuilder($cacheClass)
            ->setConstructorArgs([$directory, '.ext'])
            ->onlyMethods(['fetch', 'getNamespace'])
            ->getMock();

        $cache->expects($this->any())
            ->method('getNamespace')
            ->willReturn($namespace);

        $result = ReflectionUtil::callMethod($cache, 'getFilename', [$id]);
        $this->assertEquals(
            $directory . DIRECTORY_SEPARATOR . $expectedFileName,
            str_replace(realpath($directory), $directory, $result)
        );
    }

    /**
     * @dataProvider syncProvider
     */
    public function testSync(string $cacheClass)
    {
        $namespace = '123';

        /** @var SyncCacheInterface|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->getMockBuilder($cacheClass)
            ->disableOriginalConstructor()
            ->onlyMethods(['setNamespace', 'getNamespace'])
            ->getMock();
        $cache->expects($this->once())
            ->method('getNamespace')
            ->willReturn($namespace);
        $cache->expects($this->once())
            ->method('setNamespace')
            ->with($namespace);

        $cache->sync();
    }

    public static function getFilenameProvider(): array
    {
        return [
            [
                FilesystemCache::class,
                'test',
                null,
                '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                FilesystemCache::class,
                'test',
                'namespace',
                'namespace' . DIRECTORY_SEPARATOR . '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                FilesystemCache::class,
                'test\\\\//::""**??<<>>||file',
                'namespace\\\\//::""**??<<>>||',
                'namespace' . DIRECTORY_SEPARATOR . 'd3' . DIRECTORY_SEPARATOR . 'testfile.ext',
            ],
            [
                PhpFileCache::class,
                'test',
                null,
                '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                PhpFileCache::class,
                'test',
                'namespace',
                'namespace' . DIRECTORY_SEPARATOR . '9f' . DIRECTORY_SEPARATOR . 'test.ext',
            ],
            [
                PhpFileCache::class,
                'test\\\\//::""**??<<>>||file',
                'namespace\\\\//::""**??<<>>||',
                'namespace' . DIRECTORY_SEPARATOR . 'd3' . DIRECTORY_SEPARATOR . 'testfile.ext',
            ],
        ];
    }

    public static function syncProvider(): array
    {
        return [
            [FilesystemCache::class],
            [PhpFileCache::class],
        ];
    }
}
