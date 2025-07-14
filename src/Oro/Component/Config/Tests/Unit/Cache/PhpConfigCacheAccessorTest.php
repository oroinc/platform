<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheInterface;

class PhpConfigCacheAccessorTest extends TestCase
{
    use TempDirExtension;

    private string $cacheFile;
    private ConfigCacheInterface $cache;
    private PhpConfigCacheAccessor $accessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('PhpConfigCacheAccessor');
        $this->cache = new ConfigCache($this->cacheFile, false);

        $this->accessor = new PhpConfigCacheAccessor(function ($config) {
            if (!is_string($config)) {
                throw new \LogicException('Expected a string.');
            }
        });
    }

    public function testLoadNotExistingFile(): void
    {
        self::assertFileDoesNotExist($this->cacheFile);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The file "%s" does not exist.', $this->cacheFile));

        $this->accessor->load($this->cache);
    }

    public function testLoadWhenFileContentIsInvalid(): void
    {
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export([], true)));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The file "%s" has not valid content. Expected a string.',
            $this->cacheFile
        ));

        $this->accessor->load($this->cache);
    }

    public function testSaveWhenConfigIsInvalid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The config "%s" is not valid. Expected a string.',
            $this->cacheFile
        ));

        $this->accessor->save($this->cache, []);
    }

    public function testSaveWhenConfigIsNull(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The config "%s" is not valid. Must not be NULL.',
            $this->cacheFile
        ));

        $this->accessor->save($this->cache, null);
    }

    public function testSaveAndLoad(): void
    {
        $config = 'test';

        $this->accessor->save($this->cache, $config);
        self::assertEquals(
            $config,
            $this->accessor->load($this->cache)
        );
    }

    public function testRemove(): void
    {
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export([], true)));

        $this->accessor->remove($this->cache);
        self::assertFileDoesNotExist($this->cacheFile);
    }

    public function testRemoveWhenCacheFileDoesNotExist(): void
    {
        // guard
        self::assertFileDoesNotExist($this->cacheFile);

        $this->accessor->remove($this->cache);
        self::assertFileDoesNotExist($this->cacheFile);
    }
}
