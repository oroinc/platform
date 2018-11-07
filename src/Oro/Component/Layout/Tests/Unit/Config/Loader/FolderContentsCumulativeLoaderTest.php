<?php

namespace Oro\Component\Layout\Tests\Unit\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Layout\Config\Loader\LayoutUpdateCumulativeResourceLoader;
use Oro\Component\Layout\Tests\Unit\Fixtures\Bundle\TestBundle\TestBundle;
use Oro\Component\Testing\TempDirExtension;

class FolderContentsCumulativeLoaderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $bundleDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $tmpDir = $this->copyToTempDir('test_data', realpath(__DIR__ . '/../../Fixtures'));
        $this->bundleDir = $tmpDir . DIRECTORY_SEPARATOR . 'Bundle' . DIRECTORY_SEPARATOR . 'TestBundle';
    }

    public function testIsResourceFreshFileWasAdded()
    {
        $loader = new LayoutUpdateCumulativeResourceLoader('Resources/tmp/', -1, false);

        $bundle = new TestBundle();
        $bundleClass = get_class($bundle);
        $bundleDir = $this->bundleDir;
        $appDir = $bundleDir . '/../../app';
        $appRootDir = realpath($appDir);
        $bundleAppDir = $appRootDir . '/Resources/TestBundle';

        $loadTime = time() - 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);

        $this->assertTrue($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/tmp/added.yml');
        $fileDir = dirname($filePath);
        if (!mkdir($fileDir) && !is_dir($fileDir)) {
            return;
        }

        touch($filePath);
        $this->assertFalse($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
        unlink($filePath);
        rmdir($fileDir);
    }

    public function testIsResourceFreshFileWasDeleted()
    {
        $loader = new LayoutUpdateCumulativeResourceLoader('Resources/tmp/', -1, false);

        $bundle = new TestBundle();
        $bundleClass = get_class($bundle);
        $bundleDir = $this->bundleDir;
        $appDir = $bundleDir . '/../../app';
        $appRootDir = realpath($appDir);
        $bundleAppDir = $appRootDir . '/Resources/TestBundle';

        $loadTime = time() - 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);

        $this->assertTrue($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/tmp/added.yml');
        $fileDir = dirname($filePath);
        if (!mkdir($fileDir) && !is_dir($fileDir)) {
            return;
        }

        touch($filePath);
        $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);
        unlink($filePath);

        $this->assertFalse($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
        rmdir($fileDir);
    }

    public function testIsResourceFreshFileWasChanged()
    {
        $loader = new LayoutUpdateCumulativeResourceLoader('Resources/', -1, false);

        $bundle = new TestBundle();
        $bundleClass = get_class($bundle);
        $bundleDir = $this->bundleDir;
        $appDir = $bundleDir . '/../../app';
        $appRootDir = realpath($appDir);
        $bundleAppDir = $appRootDir . '/Resources/TestBundle';
        $relativeFilePath = 'Resources/test.yml';

        $loadTime = filemtime($bundleDir . '/' . $relativeFilePath) - 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);
        $this->assertFalse($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }
}
