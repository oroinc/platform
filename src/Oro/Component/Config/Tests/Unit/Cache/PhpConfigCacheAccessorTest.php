<?php

namespace Oro\Component\Config\Tests\Unit\Cache;

use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheInterface;

class PhpConfigCacheAccessorTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    /** @var ConfigCacheInterface */
    private $cache;

    /** @var PhpConfigCacheAccessor */
    private $accessor;

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

    public function testLoadNotExistingFile()
    {
        self::assertFileNotExists($this->cacheFile);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('The file "%s" does not exist.', $this->cacheFile));

        $this->accessor->load($this->cache);
    }

    public function testLoadWhenFileContentIsInvalid()
    {
        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export([], true)));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The file "%s" has not valid content. Expected a string.',
            $this->cacheFile
        ));

        $this->accessor->load($this->cache);
    }

    public function testSaveWhenConfigIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The config "%s" is not valid. Expected a string.',
            $this->cacheFile
        ));

        $this->accessor->save($this->cache, []);
    }

    public function testSaveWhenConfigIsNull()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The config "%s" is not valid. Must not be NULL.',
            $this->cacheFile
        ));

        $this->accessor->save($this->cache, null);
    }

    public function testSaveAndLoad()
    {
        $config = 'test';

        $this->accessor->save($this->cache, $config);
        self::assertEquals(
            $config,
            $this->accessor->load($this->cache)
        );
    }
}
