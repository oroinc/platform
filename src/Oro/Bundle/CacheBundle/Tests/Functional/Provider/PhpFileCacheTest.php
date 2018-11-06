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

    protected function setUp()
    {
        $this->provider = new PhpFileCache($this->getTempDir('oro_cache'));
    }

    public function testObjectNotImplementsSetState()
    {
        $cacheData = new \stdClass();
        $cacheId = 'test';

        $this->provider->save($cacheId, $cacheData);

        $fetchedData = $this->provider->fetch($cacheId);

        $this->assertTrue($this->provider->contains($cacheId));
        $this->assertEquals($cacheData, $fetchedData);
    }

    public function testObjectImplementsSetState()
    {
        $data = [1,2,3];
        $cacheData = new SetStateClassStub($data);
        $cacheId = 'test';

        $this->provider->save($cacheId, $cacheData, 100);

        $fetchedData = $this->provider->fetch($cacheId);

        $this->assertTrue($this->provider->contains($cacheId));
        $this->assertEquals($cacheData, $fetchedData);
    }
}
