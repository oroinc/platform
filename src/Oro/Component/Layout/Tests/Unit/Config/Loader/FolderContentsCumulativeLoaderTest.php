<?php

namespace Oro\Component\Config\Tests\Unit;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Layout\Config\Loader\LayoutUpdateCumulativeResourceLoader;
use Oro\Component\Layout\Tests\Unit\Fixtures\Bundle\TestBundle\TestBundle;

class LayoutUpdateCumulativeResourceLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testIsResourceFreshFileWasAdded()
    {
        $loader = new LayoutUpdateCumulativeResourceLoader('Resources/tmp/', -1, false);

        $bundle = new TestBundle();
        $bundleClass = get_class($bundle);
        $bundleDir = dirname((new \ReflectionClass($bundle))->getFileName());
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
        $bundleDir = dirname((new \ReflectionClass($bundle))->getFileName());
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
        $bundleDir = dirname((new \ReflectionClass($bundle))->getFileName());
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
