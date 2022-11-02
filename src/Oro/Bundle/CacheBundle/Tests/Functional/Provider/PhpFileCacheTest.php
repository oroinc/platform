<?php

namespace Oro\Bundle\CacheBundle\Tests\Functional\Provider;

use Oro\Bundle\CacheBundle\Provider\PhpFileCache;
use Oro\Bundle\CacheBundle\Tests\Functional\Stub\SetStateClassStub;
use Oro\Component\Testing\TempDirExtension;

class PhpFileCacheTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /**
     * @var PhpFileCache
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new PhpFileCache('test_namespace', 0, $this->getTempDir('oro_cache'));
    }

    public function testObjectNotImplementsSetState()
    {
        $cacheData = new \stdClass();
        $cacheId = 'test1';
        $fetchedData = $this->provider->get($cacheId, function ($item) {
            $item->set(new \stdClass());
            return $item->get();
        });
        $this->assertEquals($cacheData, $fetchedData);
    }

    public function testObjectImplementsSetState()
    {
        $data = [1,2,3];
        $cacheData = new SetStateClassStub($data);
        $cacheId = 'test2';
        $fetchedData = $this->provider->get($cacheId, function ($item) use ($cacheData) {
            $item->set($cacheData);
            return $item->get();
        });
        $this->assertEquals($cacheData, $fetchedData);
    }
}
