<?php

namespace Oro\Bundle\CacheBundle\Tests\Functional\Provider;

use Oro\Bundle\CacheBundle\Provider\PhpFileCache;
use Oro\Bundle\CacheBundle\Tests\Functional\Stub\SetStateClassStub;

class PhpFileCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var PhpFileCache
     */
    private $provider;

    protected function setUp()
    {
        do {
            $this->directory = sys_get_temp_dir() . '/oro_cache_'. uniqid('oro', true);
        } while (file_exists($this->directory));

        $this->provider = new PhpFileCache($this->directory);
    }

    protected function tearDown()
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $iterator = new \RecursiveDirectoryIterator($this->directory);

        foreach (new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isFile()) {
                @unlink($file->getRealPath());
            } elseif ($file->isDir()) {
                @rmdir($file->getRealPath());
            }
        }

        @rmdir($this->directory);
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
