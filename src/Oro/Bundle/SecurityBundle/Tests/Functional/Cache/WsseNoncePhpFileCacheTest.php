<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Cache;

use Oro\Bundle\SecurityBundle\Cache\WsseNoncePhpFileCache;
use Oro\Bundle\SecurityBundle\Tests\Util\ReflectionUtil;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\TempDirExtension;

class WsseNoncePhpFileCacheTest extends WebTestCase
{
    use TempDirExtension;

    /**
     * @var WsseNoncePhpFileCache
     */
    private $cacheProvider;

    protected function setUp()
    {
        $this->cacheProvider = new WsseNoncePhpFileCache($this->getTempDir('oro_cache'));
    }

    public function testPurgeWithNotElapsedPurgeInterval()
    {
        $this->assertNull(
            ReflectionUtil::callProtectedMethod(
                $this->cacheProvider,
                'purge',
                []
            )
        );
    }

    public function testPurgeSuccess()
    {
        $this->writePurgeStatusFilePath();
        $this->assertTrue(
            ReflectionUtil::callProtectedMethod(
                $this->cacheProvider,
                'purge',
                []
            )
        );
    }

    public function testPurgeLockFile()
    {
        $purgeStatusFilePath = $this->writePurgeStatusFilePath();
        $file = fopen($purgeStatusFilePath, 'r');
        flock($file, LOCK_EX | LOCK_NB);

        $this->assertNull(
            ReflectionUtil::callProtectedMethod(
                $this->cacheProvider,
                'purge',
                []
            )
        );
    }

    protected function writePurgeStatusFilePath()
    {
        $directory = ReflectionUtil::callProtectedMethod(
            $this->cacheProvider,
            'getDataDirectory',
            []
        );
        $purgeStatusFileName = 'cache_purge_status' . $this->cacheProvider->getExtension();
        $purgeStatusFilePath = $directory . $purgeStatusFileName;

        $content = "test content";
        $file = fopen($purgeStatusFilePath, "wb");
        fwrite($file, $content);
        fclose($file);

        return $purgeStatusFilePath;
    }
}
