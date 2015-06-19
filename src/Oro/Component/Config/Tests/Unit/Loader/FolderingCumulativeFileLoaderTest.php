<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class FolderingCumulativeFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader();

        $this->assertEquals($this->getExpectedResource(), $loader->getResource());

        /** @var CumulativeResourceInfo[] $result */
        $result = $loader->load($bundleClass, $bundleDir);

        $this->assertTrue(is_array($result));
        $this->assertCount(3, $result);
        usort(
            $result,
            function (CumulativeResourceInfo $a, CumulativeResourceInfo $b) {
                return strcmp($a->path, $b->path);
            }
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/config/another non word/test.yml'),
            $result[0]->path
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/config/bar/test.yml'),
            $result[1]->path
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/config/foo/test.yml'),
            $result[2]->path
        );
    }

    public function testRegisterResource()
    {
        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader();

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $found = $resource->getFound($bundleClass);
        sort($found);
        $this->assertEquals(
            [
                str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/config/another non word/test.yml'),
                str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/config/bar/test.yml'),
                str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/config/foo/test.yml')
            ],
            $found
        );
    }

    public function testIsResourceFreshNoChanges()
    {
        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader();

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $loadTime = $this->getLastMTime($bundleDir) + 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $this->assertTrue($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }

    public function testIsResourceFreshExistingFileWasChanged()
    {
        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader();

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $loadTime = $this->getLastMTime($bundleDir) - 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $this->assertFalse($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }

    public function testIsResourceFreshNewFileWasAdded()
    {
        $relativeFilePath = 'Resources/config/tmp/test.yml';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader();

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $loadTime = $this->getLastMTime($bundleDir) + 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $filePath = $bundleDir . '/' . $relativeFilePath;
        mkdir(dirname($filePath));
        file_put_contents($filePath, 'test: tmp');
        $result = $loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime);
        unlink($filePath);
        rmdir(dirname($filePath));
        $this->assertFalse($result);
    }

    public function testIsResourceFreshNewFileWasDeleted()
    {
        $relativeFilePath = 'Resources/config/tmp/test.yml';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader();

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $filePath = $bundleDir . '/' . $relativeFilePath;
        mkdir(dirname($filePath));
        file_put_contents($filePath, 'test: tmp');
        $loadTime = $this->getLastMTime($bundleDir) + 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);
        unlink($filePath);
        rmdir(dirname($filePath));

        $this->assertFalse($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }

    /**
     * @return FolderingCumulativeFileLoader
     */
    protected function createLoader()
    {
        return new FolderingCumulativeFileLoader(
            '{folder}',
            '\w+',
            [
                new YamlCumulativeFileLoader('Resources/config/{folder}/test.yml'),
                new YamlCumulativeFileLoader('Resources/config/{folder}/none.yml'),
                new YamlCumulativeFileLoader('Resources/config/another non word/test.yml')
            ]
        );
    }

    /**
     * @return string
     */
    protected function getExpectedResource()
    {
        return
            'Foldering:'
            . 'Resources/config/{folder}/test.yml;'
            . 'Resources/config/{folder}/none.yml;'
            . 'Resources/config/another non word/test.yml';
    }

    /**
     * @param string $bundleDir
     * @return int
     */
    protected function getLastMTime($bundleDir)
    {
        $files = [
            $bundleDir . '/Resources/config/another non word/test.yml',
            $bundleDir . '/Resources/config/bar/test.yml',
            $bundleDir . '/Resources/config/foo/test.yml'
        ];

        $result = 0;
        foreach ($files as $file) {
            $time = filemtime($file);
            if ($time > $result) {
                $result = $time;
            }
        }

        return $result;
    }
}
