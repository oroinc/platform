<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeFileLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoader()
    {
        $relativeFilePath = 'Resources/config/test.yml';

        $loader = $this->createLoader($relativeFilePath);

        $data             = ['test' => 123];
        $bundle           = new TestBundle1();
        $bundleDir        = dirname((new \ReflectionClass($bundle))->getFileName());
        $expectedFilePath = $bundleDir . '/' . $relativeFilePath;
        $expectedFilePath = str_replace('/', DIRECTORY_SEPARATOR, $expectedFilePath);

        $expectedResource = new CumulativeResourceInfo(
            get_class($bundle),
            'test',
            $expectedFilePath,
            $data
        );

        $loader->expects($this->once())
            ->method('loadFile')
            ->with($expectedFilePath)
            ->will($this->returnValue($data));

        $this->assertEquals($relativeFilePath, $loader->getResource());

        $resource = $loader->load(get_class($bundle), $bundleDir);
        $this->assertEquals($expectedResource, $resource);
    }

    /**
     * @dataProvider filePathProvider
     */
    public function testFilePath($relativeFilePath, $expectedRelativeFilePath, $expectedResource)
    {
        $loader = $this->createLoader($relativeFilePath);

        $this->assertEquals($expectedRelativeFilePath, $loader->getRelativeFilePath());
        $this->assertEquals($expectedResource, $loader->getResource());
    }

    public function testRegisterFoundResource()
    {
        $relativeFilePath = 'Resources/config/test.yml';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader($relativeFilePath);

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $expectedResource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $expectedResource->addFound(
            $bundleClass,
            str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/' . $relativeFilePath)
        );
        $this->assertEquals($expectedResource, $resource);
    }

    public function testIsResourceFreshNoChanges()
    {
        $relativeFilePath = 'Resources/config/test.yml';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader($relativeFilePath);

        $loadTime = filemtime($bundleDir . '/' . $relativeFilePath) + 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $this->assertTrue($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }

    public function testIsResourceFreshNoFile()
    {
        $relativeFilePath = 'Resources/config/none.tmp';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader($relativeFilePath);

        $loadTime = filemtime($bundleDir) + 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $this->assertTrue($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }

    public function testIsResourceFreshExistingFileWasChanged()
    {
        $relativeFilePath = 'Resources/config/test.yml';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader($relativeFilePath);

        $loadTime = filemtime($bundleDir . '/' . $relativeFilePath) - 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $this->assertFalse($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }

    public function testIsResourceFreshNewFileWasAdded()
    {
        $relativeFilePath = 'Resources/config/test.tmp';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader($relativeFilePath);

        $loadTime = filemtime($bundleDir) + 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $filePath = $bundleDir . '/' . $relativeFilePath;
        file_put_contents($filePath, 'test');
        $result = $loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime);
        unlink($filePath);
        $this->assertFalse($result);
    }

    public function testIsResourceFreshNewFileWasDeleted()
    {
        $relativeFilePath = 'Resources/config/test.tmp';

        $bundle      = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir   = dirname((new \ReflectionClass($bundle))->getFileName());

        $loader = $this->createLoader($relativeFilePath);

        $filePath = $bundleDir . '/' . $relativeFilePath;
        file_put_contents($filePath, 'test');
        $loadTime = filemtime($bundleDir . '/' . $relativeFilePath) + 1;
        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);
        unlink($filePath);

        $this->assertFalse($loader->isResourceFresh($bundleClass, $bundleDir, '', $resource, $loadTime));
    }

    public function filePathProvider()
    {
        return [
            [
                'Resources/config/test.yml',
                str_replace('/', DIRECTORY_SEPARATOR, '/Resources/config/test.yml'),
                'Resources/config/test.yml'
            ],
            [
                '/Resources/config/test.yml',
                str_replace('/', DIRECTORY_SEPARATOR, '/Resources/config/test.yml'),
                'Resources/config/test.yml'
            ],
            [
                'Resources\config\test.yml',
                str_replace('/', DIRECTORY_SEPARATOR, '/Resources/config/test.yml'),
                'Resources/config/test.yml'
            ],
            [
                '\Resources\config\test.yml',
                str_replace('/', DIRECTORY_SEPARATOR, '/Resources/config/test.yml'),
                'Resources/config/test.yml'
            ],
            [
                'test.yml',
                str_replace('/', DIRECTORY_SEPARATOR, '/test.yml'),
                'test.yml'
            ],
            [
                '/test.yml',
                str_replace('/', DIRECTORY_SEPARATOR, '/test.yml'),
                'test.yml'
            ],
            [
                '\test.yml',
                str_replace('/', DIRECTORY_SEPARATOR, '/test.yml'),
                'test.yml'
            ],
        ];
    }

    /**
     * @param string $relativeFilePath
     * @return CumulativeFileLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createLoader($relativeFilePath)
    {
        return $this->getMockForAbstractClass(
            'Oro\Component\Config\Loader\CumulativeFileLoader',
            [$relativeFilePath]
        );
    }
}
