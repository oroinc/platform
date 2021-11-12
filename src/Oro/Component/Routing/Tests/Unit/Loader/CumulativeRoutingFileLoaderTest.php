<?php

namespace Oro\Component\Routing\Tests\Unit\Loader;

use Oro\Component\Routing\Loader\CumulativeRoutingFileLoader;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class CumulativeRoutingFileLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $kernel;

    /** @var RouteOptionsResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $routeOptionsResolver;

    /** @var LoaderResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $loaderResolver;

    /** @var CumulativeRoutingFileLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->routeOptionsResolver = $this->createMock(RouteOptionsResolverInterface::class);
        $this->loaderResolver = $this->createMock(LoaderResolverInterface::class);

        $this->loader = new CumulativeRoutingFileLoader(
            $this->kernel,
            $this->routeOptionsResolver,
            ['Resources/config/routing.yml'],
            'auto'
        );
        $this->loader->setResolver($this->loaderResolver);
    }

    public function testSupports(): void
    {
        self::assertTrue($this->loader->supports(null, 'auto'));
        self::assertFalse($this->loader->supports(null, 'another'));
    }

    public function testLoad(): void
    {
        $rootDir = str_replace('\\', '/', realpath(__DIR__ . '/../Fixtures/Bundles'));

        $loadedRoutes = new RouteCollection();
        $loadedRoutes->add('route1', new Route('/route1'));
        $loadedRoutes->add('route2', new Route('/route2', [], [], ['priority' => 1]));

        /** @var \PHPUnit\Framework\MockObject\MockObject[] $bundles */
        $bundles = [
            'bundle1' => $this->createMock(BundleInterface::class),
            'bundle2' => $this->createMock(BundleInterface::class),
            'bundle3' => $this->createMock(BundleInterface::class)
        ];

        $bundles['bundle1']->expects(self::any())
            ->method('getPath')
            ->willReturn($rootDir . '/Bundle1');
        $bundles['bundle2']->expects(self::any())
            ->method('getPath')
            ->willReturn($rootDir . '/Bundle2');
        $bundles['bundle3']->expects(self::any())
            ->method('getPath')
            ->willReturn($rootDir . '/Bundle3');

        $this->kernel->expects(self::once())
            ->method('getBundles')
            ->willReturn($bundles);

        $yamlLoader = $this->createMock(LoaderInterface::class);

        $this->loaderResolver->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(
                function ($resource) use ($rootDir, $yamlLoader) {
                    $resource = str_replace('\\', '/', $resource);
                    if ($resource === str_replace('\\', '/', $rootDir . '/Bundle1/Resources/config/routing.yml')) {
                        return $yamlLoader;
                    }
                    if ($resource === str_replace('\\', '/', $rootDir . '/Bundle2/Resources/config/routing.yml')) {
                        return $yamlLoader;
                    }

                    return null;
                }
            );

        $yamlLoader->expects(self::exactly(2))
            ->method('load')
            ->willReturnCallback(
                function ($resource) use ($rootDir, $loadedRoutes) {
                    $resource = str_replace('\\', '/', $resource);
                    if ($resource === str_replace('\\', '/', $rootDir . '/Bundle1/Resources/config/routing.yml')) {
                        return $loadedRoutes;
                    }
                    if ($resource === str_replace('\\', '/', $rootDir . '/Bundle2/Resources/config/routing.yml')) {
                        return new RouteCollection();
                    }

                    return null;
                }
            );

        $this->routeOptionsResolver->expects(self::exactly(2))
            ->method('resolve')
            ->withConsecutive(
                [
                    $this->identicalTo($loadedRoutes->get('route2')),
                    $this->isInstanceOf(RouteCollectionAccessor::class)
                ],
                [
                    $this->identicalTo($loadedRoutes->get('route1')),
                    $this->isInstanceOf(RouteCollectionAccessor::class)
                ]
            );

        $routes = $this->loader->load(null, 'auto');

        self::assertEquals(
            ['route2', 'route1'],
            array_keys($routes->all())
        );
    }
}
