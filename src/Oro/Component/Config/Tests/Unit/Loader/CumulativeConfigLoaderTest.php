<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CumulativeConfigLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param object $bundle
     *
     * @return string
     */
    private function getBundleDir($bundle)
    {
        return dirname((new \ReflectionClass($bundle))->getFileName());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $name must not be empty.
     */
    public function testConstructorWithEmptyName()
    {
        new CumulativeConfigLoader('', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $resourceLoader must not be empty.
     */
    public function testConstructorWithNullResourceLoader()
    {
        new CumulativeConfigLoader('test', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $resourceLoader must not be empty.
     */
    public function testConstructorWithEmptyResourceLoader()
    {
        new CumulativeConfigLoader('test', []);
    }

    public function testConstructorWithOneResourceLoader()
    {
        $resourceLoader = $this->createMock(CumulativeResourceLoader::class);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $this->assertAttributeCount(1, 'resourceLoaders', $loader);
    }

    public function testConstructorWithSeveralResourceLoader()
    {
        $resourceLoader1 = $this->createMock(CumulativeResourceLoader::class);
        $resourceLoader2 = $this->createMock(CumulativeResourceLoader::class);

        $loader = new CumulativeConfigLoader('test', [$resourceLoader1, $resourceLoader2]);
        $this->assertAttributeCount(2, 'resourceLoaders', $loader);
    }

    public function testRegisterResources()
    {
        $bundle1 = new TestBundle1();
        $bundle1Class = get_class($bundle1);
        $bundle1Dir = $this->getBundleDir($bundle1);
        $bundle2 = new TestBundle2();
        $bundle2Class = get_class($bundle2);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/test.yml');
        $resourceLoader2 = new YamlCumulativeFileLoader('Resources/config/foo/test.yml');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => $bundle1Class, 'TestBundle2' => $bundle2Class]);

        $container = new ContainerBuilder();
        $loader = new CumulativeConfigLoader('test', [$resourceLoader1, $resourceLoader2]);
        $loader->registerResources($container);

        $expectedResource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader1, $resourceLoader2])
        );
        $expectedResource->addFound(
            $bundle1Class,
            str_replace('/', DIRECTORY_SEPARATOR, $bundle1Dir . '/Resources/config/test.yml')
        );
        $expectedResource->addFound(
            $bundle1Class,
            str_replace('/', DIRECTORY_SEPARATOR, $bundle1Dir . '/Resources/config/foo/test.yml')
        );

        $this->assertCount(1, $container->getResources());
        $this->assertEquals($expectedResource, $container->getResources()[0]);
    }

    public function testGetResources()
    {
        $bundle1 = new TestBundle1();
        $bundle1Class = get_class($bundle1);
        $bundle1Dir = $this->getBundleDir($bundle1);
        $bundle2 = new TestBundle2();
        $bundle2Class = get_class($bundle2);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/test.yml');
        $resourceLoader2 = new YamlCumulativeFileLoader('Resources/config/foo/test.yml');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => $bundle1Class, 'TestBundle2' => $bundle2Class]);

        $loader = new CumulativeConfigLoader('test', [$resourceLoader1, $resourceLoader2]);
        $resource = $loader->getResources();

        $expectedResource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader1, $resourceLoader2])
        );
        $expectedResource->addFound(
            $bundle1Class,
            str_replace('/', DIRECTORY_SEPARATOR, $bundle1Dir . '/Resources/config/test.yml')
        );
        $expectedResource->addFound(
            $bundle1Class,
            str_replace('/', DIRECTORY_SEPARATOR, $bundle1Dir . '/Resources/config/foo/test.yml')
        );

        $this->assertEquals($expectedResource, $resource);
    }

    public function testLoad()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $resourceLoader = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $container = new ContainerBuilder();
        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load($container);

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/' . $resourceRelativePath),
                    ['test' => 123]
                )
            ],
            $result
        );

        $expectedResource = new CumulativeResource(
            'test',
            new CumulativeResourceLoaderCollection([$resourceLoader])
        );
        $expectedResource->addFound(
            get_class($bundle),
            str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/' . $resourceRelativePath)
        );
        $this->assertCount(1, $container->getResources());
        $this->assertEquals($expectedResource, $container->getResources()[0]);
    }

    public function testLoadWithAppRootDirectory()
    {
        $pathWithoutResources = '/config/test.yml';
        $resourceRelativePath = 'Resources' . $pathWithoutResources;
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $appRootDir = realpath($bundleDir . '/../../app');
        $resourceLoader = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->setAppRootDir($appRootDir);

        $container = new ContainerBuilder();
        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load($container);

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    str_replace(
                        '/',
                        DIRECTORY_SEPARATOR,
                        $appRootDir . '/Resources/TestBundle1' . $pathWithoutResources
                    ),
                    ['test' => 456]
                )
            ],
            $result
        );
        CumulativeResourceManager::getInstance()->setAppRootDir(null);
    }

    public function testLoadWithoutContainer()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $resourceLoader = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load();

        $this->assertEquals(
            [
                new CumulativeResourceInfo(
                    get_class($bundle),
                    'test',
                    str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/' . $resourceRelativePath),
                    ['test' => 123]
                )
            ],
            $result
        );
    }

    public function testLoadWhenNoResources()
    {
        $bundle = new TestBundle1();
        $bundleDir = $this->getBundleDir($bundle);
        $resourceLoader = $this->createMock(CumulativeResourceLoader::class);
        $resourceLoader->expects($this->once())
            ->method('load')
            ->with(get_class($bundle), $bundleDir)
            ->willReturn(null);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load();

        $this->assertCount(0, $result);
    }

    public function testLoadWhenResourceLoaderReturnsArray()
    {
        $bundle1 = new TestBundle1();
        $bundle1Dir = $this->getBundleDir($bundle1);
        $bundle2 = new TestBundle2();
        $bundle2Dir = $this->getBundleDir($bundle2);
        $resourceLoader = $this->createMock(CumulativeResourceLoader::class);
        $resource1 = new CumulativeResourceInfo(get_class($bundle1), 'res1', 'res1', []);
        $resource2 = new CumulativeResourceInfo(get_class($bundle1), 'res2', 'res2', []);
        $resourceLoader->expects($this->at(0))
            ->method('load')
            ->with(get_class($bundle1), $bundle1Dir)
            ->willReturn([$resource1, $resource2]);
        $resource3 = new CumulativeResourceInfo(get_class($bundle2), 'res3', 'res3', []);
        $resourceLoader->expects($this->at(1))
            ->method('load')
            ->with(get_class($bundle2), $bundle2Dir)
            ->willReturn($resource3);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle1), 'TestBundle2' => get_class($bundle2)]);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load();

        $this->assertEquals([$resource1, $resource2, $resource3], $result);
    }

    public function testLoadEmptyFileWithoutWarnings()
    {
        $bundle1 = new TestBundle1();
        $bundle1Dir = $this->getBundleDir($bundle1);

        $resource1 = new CumulativeResourceInfo(get_class($bundle1), 'empty', 'empty', []);

        $resourceLoader = $this->createMock(CumulativeResourceLoader::class);
        $resourceLoader->expects($this->once())
            ->method('load')
            ->with(get_class($bundle1), $bundle1Dir)
            ->willReturn([$resource1]);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle1)]);

        $loader = new CumulativeConfigLoader('empty', $resourceLoader);
        $result = $loader->load();

        $this->assertEquals([$resource1], $result);
    }

    public function testYamlCumulativeFileLoaderImports()
    {
        $bundle1 = new TestBundle1();
        $bundleClass = get_class($bundle1);
        $bundleDir = $this->getBundleDir($bundle1);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/datagrid/success/parent.yml');
        $resource = $resourceLoader1->load($bundleClass, $bundleDir);

        $this->assertArrayNotHasKey('imports', $resource->data); // import must be transparent
        $this->assertArrayHasKey('test', $resource->data);
        $this->assertEquals($resource->data['test'], 'success');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Circular import detected
     */
    public function testYamlCumulativeFileLoaderImportsInfiniteLoop()
    {
        $bundle1 = new TestBundle1();
        $bundleClass = get_class($bundle1);
        $bundleDir = $this->getBundleDir($bundle1);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/datagrid/loop/parent.yml');
        $resourceLoader1->load($bundleClass, $bundleDir);
    }
}
