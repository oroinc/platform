<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\ResourceProvider;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\LastModificationDateProvider;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\ThemeResourceProvider;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\Bundle\TestBundle\TestBundle;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThemeResourceProviderTest extends TestCase
{
    use TempDirExtension;

    private LastModificationDateProvider&MockObject $lastModificationDateProvider;
    private LayoutUpdateLoaderInterface&MockObject $loader;
    private BlockViewCache&MockObject $blockViewCache;
    private string $cacheFile;
    private ThemeResourceProvider $provider;

    #[\Override]
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

    private function getPath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    public function testFindApplicableResources(): void
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

    public function testGetResourcesWhenCachedDataDoesNotExist(): void
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

    public function testGetResourcesWhenCachedDataExist(): void
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
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', \var_export($cachedResources, true)));

        $this->assertEquals($cachedResources, $this->provider->getResources());
    }
}
