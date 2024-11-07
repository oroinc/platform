<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Cache;

use Oro\Bundle\SecurityBundle\Cache\FilesystemCacheInstantiator;
use Oro\Component\Testing\TempDirExtension;

class FilesystemCacheInstantiatorTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private FilesystemCacheInstantiator $instantiator;

    #[\Override]
    protected function setUp(): void
    {
        $this->instantiator = new FilesystemCacheInstantiator(123, $this->getTempDir('someDir'));
    }

    public function testGetCacheInstanceMultipleTimes(): void
    {
        $cache = $this->instantiator->getCacheInstance('testNamespace');
        $cache1 = $this->instantiator->getCacheInstance('testNamespace');

        self::assertSame($cache, $cache1);
    }

    public function testGetCacheInstanceForDifferentNamespaces(): void
    {
        $cache = $this->instantiator->getCacheInstance('testNamespace');
        $cache1 = $this->instantiator->getCacheInstance('testNamespace1');

        self::assertNotSame($cache, $cache1);
    }
}
