<?php

namespace Oro\Component\Layout\Tests\Unit\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Layout\Config\Loader\LayoutUpdateCumulativeResourceLoader;
use Oro\Component\Layout\Tests\Unit\Fixtures\Bundle\TestBundle\TestBundle;

class FolderContentsCumulativeLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CopyFixturesToTemp */
    private $copier;

    /** @var string */
    private $bundleDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $target = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'test_data';
        $source = realpath(__DIR__ . '/../../Fixtures');
        $this->copier = new CopyFixturesToTemp($target, $source);
        $this->copier->copy();
        $this->bundleDir = $target . DIRECTORY_SEPARATOR . 'Bundle' . DIRECTORY_SEPARATOR . 'TestBundle';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->copier->delete();
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
