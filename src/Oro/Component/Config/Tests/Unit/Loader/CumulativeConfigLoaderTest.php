<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;

class CumulativeConfigLoaderTest extends \PHPUnit_Framework_TestCase
{
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
            ->setBundles(['TestBundle1' => $bundle1Class, 'TestBundle2' => $bundle2Class])
            ->addResourceLoader('test_group', [$resourceLoader1, $resourceLoader2]);

        $container = new ContainerBuilder();
        $loader    = new CumulativeConfigLoader($container);
        $loader->registerResources('test_group');

        $expectedResource = new CumulativeResource('test_group');
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The container builder must not be null.
     */
    public function testRegisterResourcesWithoutContainer()
    {
        $loader = new CumulativeConfigLoader();
        $loader->registerResources('test_group');
    }

    public function testLoad()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle               = new TestBundle1();
        $bundleDir            = dirname((new \ReflectionClass($bundle))->getFileName());
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->addResourceLoader('test_group', $resourceLoader);

        $container = new ContainerBuilder();
        $loader    = new CumulativeConfigLoader($container);
        $result    = $loader->load('test_group');

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

        $expectedResource = new CumulativeResource('test_group');
        $expectedResource->addFound(
            get_class($bundle),
            str_replace('/', DIRECTORY_SEPARATOR, $bundleDir . '/Resources/config/test.yml')
        );
        $this->assertCount(1, $container->getResources());
        $this->assertEquals($expectedResource, $container->getResources()[0]);
    }

    public function testLoadWithoutContainer()
    {
        $resourceRelativePath = 'Resources/config/test.yml';
        $bundle               = new TestBundle1();
        $bundleDir            = dirname((new \ReflectionClass($bundle))->getFileName());
        $resourceLoader       = new YamlCumulativeFileLoader($resourceRelativePath);

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->addResourceLoader('test_group', $resourceLoader);

        $loader = new CumulativeConfigLoader();
        $result = $loader->load('test_group');

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
            ->setBundles(['TestBundle1' => get_class($bundle)])
            ->addResourceLoader('test_group', $resourceLoader);

        $loader = new CumulativeConfigLoader();
        $result = $loader->load('test_group');

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
            ->setBundles(['TestBundle1' => get_class($bundle1), 'TestBundle2' => get_class($bundle2)])
            ->addResourceLoader('test_group', $resourceLoader);

        $loader = new CumulativeConfigLoader();
        $result = $loader->load('test_group');

        $this->assertEquals([$resource1, $resource2, $resource3], $result);
    }
}
