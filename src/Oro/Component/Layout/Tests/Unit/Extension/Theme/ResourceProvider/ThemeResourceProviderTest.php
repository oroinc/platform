<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\ResourceProvider;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\LastModificationDateProvider;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\Bundle\TestBundle\TestBundle;
use Oro\Component\Testing\TempDirExtension;

class ThemeResourceProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var ThemeResourceProvider */
    private $provider;

    /** @var string */
    private $cacheFile;

    /** @var LastModificationDateProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lastModificationDateProvider;

    /** @var LayoutUpdateLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $loader;

    /** @var BlockViewCache|\PHPUnit\Framework\MockObject\MockObject */
    private $blockViewCache;

    protected function setUp(): void
    {
        $this->lastModificationDateProvider = $this->createMock(LastModificationDateProvider::class);
        $this->loader = $this->createMock(LayoutUpdateLoaderInterface::class);
        $this->blockViewCache = $this->createMock(BlockViewCache::class);

        $this->cacheFile = $this->getTempFile('ThemeResourceProvider');

        $this->provider = new ThemeResourceProvider(
            $this->cacheFile,
            false,
            $this->lastModificationDateProvider,
            $this->loader,
            $this->blockViewCache,
            [
                '#Resources/views/layouts/[\w\-]+/theme.yml$#',
                '#Resources/views/layouts/[\w\-]+/config/[^/]+.yml$#'
            ]
        );
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getPath($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function testFindApplicableResources()
    {
        $bundleDir = dirname((new \ReflectionClass(TestBundle::class))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle' => TestBundle::class])
            ->setAppRootDir($appRootDir);

        $this->loader->expects($this->once())
            ->method('getUpdateFileNamePatterns')
            ->willReturn(['/\.yml$/']);

        $paths = [
            'oro-default',
            'oro-default/page'
        ];

        $resourcePath = $appRootDir . $bundleDir . '/Resources/views/layouts';

        $this->assertEquals(
            [
                $this->getPath($resourcePath . '/oro-default/resource1.yml'),
                $this->getPath($resourcePath . '/oro-default/page/resource2.yml')
            ],
            $this->provider->findApplicableResources($paths)
        );
    }

    public function testGetResourcesWhenCachedDataDoesNotExist()
    {
        $bundleDir = dirname((new \ReflectionClass(TestBundle::class))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle' => TestBundle::class])
            ->setAppRootDir($appRootDir);

        $this->loader->expects($this->once())
            ->method('getUpdateFileNamePatterns')
            ->willReturn(['/\.yml$/']);

        $this->lastModificationDateProvider->expects($this->once())
            ->method('updateLastModificationDate')
            ->with($this->isInstanceOf(\DateTime::class));

        $this->blockViewCache->expects($this->once())
            ->method('reset');

        $resourcePath = $appRootDir . $bundleDir . '/Resources/views/layouts/oro-default';
        $result = [
            'oro-default' => [
                $this->getPath($resourcePath . '/resource1.yml'),
                'page' => [
                    $this->getPath($resourcePath . '/page/resource2.yml')
                ]
            ]
        ];

        $this->assertEquals($result, $this->provider->getResources());
    }

    public function testGetResourcesWhenCachedDataExist()
    {
        $bundleDir = dirname((new \ReflectionClass(TestBundle::class))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['TestBundle' => TestBundle::class])
            ->setAppRootDir($appRootDir);

        $this->loader->expects($this->never())
            ->method('getUpdateFileNamePatterns');

        $this->lastModificationDateProvider->expects($this->never())
            ->method('updateLastModificationDate');

        $this->blockViewCache->expects($this->never())
            ->method('reset');

        $cachedResources = [
            'oro-default' => [
                '/Resources/views/layouts/oro-default/resource1.yml'
            ]
        ];
        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export($cachedResources, true)));

        $this->assertEquals($cachedResources, $this->provider->getResources());
    }
}
