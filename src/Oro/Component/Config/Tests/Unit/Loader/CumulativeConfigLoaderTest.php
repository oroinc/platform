<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;

class CumulativeConfigLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $name must not be empty.
     */
    public function testConstructorWithNullName()
    {
        $loader = new CumulativeConfigLoader(null, null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $name must not be empty.
     */
    public function testConstructorWithEmptyName()
    {
        $loader = new CumulativeConfigLoader('', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $resourceLoader must not be empty.
     */
    public function testConstructorWithNullResourceLoader()
    {
        $loader = new CumulativeConfigLoader('test', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $resourceLoader must not be empty.
     */
    public function testConstructorWithEmptyResourceLoader()
    {
        $loader = new CumulativeConfigLoader('test', []);
    }

    public function testConstructorWithOneResourceLoader()
    {
        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $this->assertAttributeCount(1, 'resourceLoaders', $loader);
    }

    public function testConstructorWithSeveralResourceLoader()
    {
        $resourceLoader1 = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resourceLoader2 = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');

        $loader = new CumulativeConfigLoader('test', [$resourceLoader1, $resourceLoader2]);
        $this->assertAttributeCount(2, 'resourceLoaders', $loader);
    }

    public function testRegisterResources()
    {
        $bundle1      = new TestBundle1();
        $bundle1Class = get_class($bundle1);
        $bundle1Dir   = dirname((new \ReflectionClass($bundle1))->getFileName());
        $bundle2      = new TestBundle2();
        $bundle2Class = get_class($bundle2);

        $resourceLoader1 = new YamlCumulativeFileLoader('Resources/config/test.yml');
        $resourceLoader2 = new YamlCumulativeFileLoader('Resources/config/foo/test.yml');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => $bundle1Class, 'TestBundle2' => $bundle2Class]);

        $container = new ContainerBuilder();
        $loader    = new CumulativeConfigLoader('test', [$resourceLoader1, $resourceLoader2]);
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

    public function testLoad()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle               = new TestBundle1();
        $bundleDir            = dirname((new \ReflectionClass($bundle))->getFileName());
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $container = new ContainerBuilder();
        $loader    = new CumulativeConfigLoader('test', $resourceLoader);
        $result    = $loader->load($container);

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
        $bundle               = new TestBundle1();
        $bundleDir            = dirname((new \ReflectionClass($bundle))->getFileName());
        $appRootDir           = realpath($bundleDir . '/../../app');
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->setAppRootDir($appRootDir);

        $container = new ContainerBuilder();
        $loader    = new CumulativeConfigLoader('test', $resourceLoader);
        $result    = $loader->load($container);

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
        $bundle               = new TestBundle1();
        $bundleDir            = dirname((new \ReflectionClass($bundle))->getFileName());
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

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
        $bundle         = new TestBundle1();
        $bundleDir      = dirname((new \ReflectionClass($bundle))->getFileName());
        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resourceLoader->expects($this->once())
            ->method('load')
            ->with(get_class($bundle), $bundleDir)
            ->will($this->returnValue(null));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)]);

        $loader = new CumulativeConfigLoader('test', $resourceLoader);
        $result = $loader->load();

        $this->assertCount(0, $result);
    }

    public function testLoadWhenResourceLoaderReturnsArray()
    {
        $bundle1        = new TestBundle1();
        $bundle1Dir     = dirname((new \ReflectionClass($bundle1))->getFileName());
        $bundle2        = new TestBundle2();
        $bundle2Dir     = dirname((new \ReflectionClass($bundle2))->getFileName());
        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resource1      = new CumulativeResourceInfo(get_class($bundle1), 'res1', 'res1', []);
        $resource2      = new CumulativeResourceInfo(get_class($bundle1), 'res2', 'res2', []);
        $resourceLoader->expects($this->at(0))
            ->method('load')
            ->with(get_class($bundle1), $bundle1Dir)
            ->will($this->returnValue([$resource1, $resource2]));
        $resource3 = new CumulativeResourceInfo(get_class($bundle2), 'res3', 'res3', []);
        $resourceLoader->expects($this->at(1))
            ->method('load')
            ->with(get_class($bundle2), $bundle2Dir)
            ->will($this->returnValue($resource3));

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
        $bundle1Dir = dirname((new \ReflectionClass($bundle1))->getFileName());

        $resource1 = new CumulativeResourceInfo(get_class($bundle1), 'empty', 'empty', []);

        $resourceLoader = $this->getMock('Oro\Component\Config\Loader\CumulativeResourceLoader');
        $resourceLoader->expects($this->once())
            ->method('load')
            ->with(get_class($bundle1), $bundle1Dir)
            ->will($this->returnValue([$resource1]));

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle1)]);

        $loader = new CumulativeConfigLoader('empty', $resourceLoader);
        $result = $loader->load();

        $this->assertEquals([$resource1], $result);
    }
}
