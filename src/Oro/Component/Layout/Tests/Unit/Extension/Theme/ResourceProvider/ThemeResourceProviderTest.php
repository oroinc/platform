<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\ResourceProvider;

use Doctrine\Common\Cache\Cache;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\Bundle\TestBundle\TestBundle;

class ThemeResourceProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeResourceProvider */
    protected $provider;

    /** @var LayoutUpdateLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $loader;

    /** @var BlockViewCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $blockViewCache;

    /** @var array */
    protected $excludedPaths = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->loader = $this->createMock(LayoutUpdateLoaderInterface::class);
        $this->blockViewCache = $this->getMockBuilder(BlockViewCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ThemeResourceProvider($this->loader, $this->blockViewCache, $this->excludedPaths);
    }

    public function testFindApplicableResources()
    {
        $bundleDir = dirname((new \ReflectionClass(TestBundle::class))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle' => TestBundle::class])
            ->setAppRootDir($appRootDir);

        $this->loader
            ->expects($this->once())
            ->method('getUpdateFileNamePatterns')
            ->will($this->returnValue(['/\.yml$/']));

        $paths = [
            'oro-default',
            'oro-default/page'
        ];

        $resourcePath = $appRootDir . $bundleDir . '/Resources/views/layouts';

        $this->assertEquals(
            [
                str_replace('/', DIRECTORY_SEPARATOR, $resourcePath . '/oro-default/resource1.yml'),
                str_replace('/', DIRECTORY_SEPARATOR, $resourcePath . '/oro-default/page/resource2.yml')
            ],
            $this->provider->findApplicableResources($paths)
        );
    }

    public function testGetResources()
    {
        $bundleDir = dirname((new \ReflectionClass(TestBundle::class))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle' => TestBundle::class])
            ->setAppRootDir($appRootDir);

        $this->loader
            ->expects($this->once())
            ->method('getUpdateFileNamePatterns')
            ->will($this->returnValue(['/\.yml$/']));

        $resourcePath = $appRootDir . $bundleDir . '/Resources/views/layouts/oro-default';
        $result = [
            'oro-default' => [
                str_replace('/', DIRECTORY_SEPARATOR, $resourcePath . '/resource1.yml'),
                'page' => [
                    str_replace('/', DIRECTORY_SEPARATOR, $resourcePath . '/page/resource2.yml')
                ]
            ]
        ];

        $this->assertEquals($result, $this->provider->getResources());
    }

    public function testLoadResources()
    {
        $bundleDir = dirname((new \ReflectionClass(TestBundle::class))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle' => TestBundle::class])
            ->setAppRootDir($appRootDir);

        $this->loader
            ->expects($this->once())
            ->method('getUpdateFileNamePatterns')
            ->will($this->returnValue(['/\.yml$/']));

        $this->provider->loadResources();
    }

    public function testLoadResourcesWithCache()
    {
        $bundleDir = dirname((new \ReflectionClass(TestBundle::class))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle' => TestBundle::class])
            ->setAppRootDir($appRootDir);

        $this->loader
            ->expects($this->once())
            ->method('getUpdateFileNamePatterns')
            ->will($this->returnValue(['/\.yml$/']));

        $resourcePath = $appRootDir . $bundleDir . '/Resources/views/layouts/oro-default';
        $result = [
            'oro-default' => [
                str_replace('/', DIRECTORY_SEPARATOR, $resourcePath . '/resource1.yml'),
                'page' => [
                    str_replace('/', DIRECTORY_SEPARATOR, $resourcePath . '/page/resource2.yml')
                ]
            ]
        ];

        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->createMock(Cache::class);
        $cache->expects($this->exactly(2))
            ->method('save');

        $this->blockViewCache->expects($this->exactly(1))
            ->method('reset');

        $this->provider->setCache($cache);
        $this->provider->loadResources();
    }
}
