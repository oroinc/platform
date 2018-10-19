<?php

namespace Oro\Component\Routing\Tests\Unit\Loader;

use Oro\Component\Routing\Loader\CumulativeRoutingFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class CumulativeRoutingFileLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $kernel;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $routeOptionsResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $loaderResolver;

    /** @var CumulativeRoutingFileLoader */
    private $loader;

    protected function setUp()
    {
        $this->kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');

        $this->routeOptionsResolver = $this->createMock('Oro\Component\Routing\Resolver\RouteOptionsResolverInterface');

        $this->loaderResolver = $this->createMock('Symfony\Component\Config\Loader\LoaderResolverInterface');

        $this->loader = new CumulativeRoutingFileLoader(
            $this->kernel,
            $this->routeOptionsResolver,
            ['Resources/config/routing.yml'],
            'auto'
        );
        $this->loader->setResolver($this->loaderResolver);
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports(null, 'auto'));
        $this->assertFalse($this->loader->supports(null, 'another'));
    }

    public function testLoad()
    {
        $rootDir = str_replace('\\', '/', realpath(__DIR__ . '/../Fixtures/Bundles'));

        $loadedRoutes = new RouteCollection();
        $loadedRoutes->add('route1', new Route('/route1'));
        $loadedRoutes->add('route2', new Route('/route2', [], [], ['priority' => 1]));

        /** @var \PHPUnit\Framework\MockObject\MockObject[] $bundles */
        $bundles = [
            'bundle1' => $this->createMock('Symfony\Component\HttpKernel\Bundle\BundleInterface'),
            'bundle2' => $this->createMock('Symfony\Component\HttpKernel\Bundle\BundleInterface'),
            'bundle3' => $this->createMock('Symfony\Component\HttpKernel\Bundle\BundleInterface')
        ];

        $bundles['bundle1']->expects($this->any())
            ->method('getPath')
            ->willReturn($rootDir . '/Bundle1');
        $bundles['bundle2']->expects($this->any())
            ->method('getPath')
            ->willReturn($rootDir . '/Bundle2');
        $bundles['bundle3']->expects($this->any())
            ->method('getPath')
            ->willReturn($rootDir . '/Bundle3');

        $this->kernel->expects($this->once())
            ->method('getBundles')
            ->willReturn($bundles);

        $yamlLoader = $this->createMock('Symfony\Component\Config\Loader\LoaderInterface');

        $this->loaderResolver->expects($this->exactly(2))
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

        $yamlLoader->expects($this->exactly(2))
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

        $this->routeOptionsResolver->expects($this->at(0))
            ->method('resolve')
            ->with(
                $this->identicalTo($loadedRoutes->get('route1')),
                $this->isInstanceOf('Oro\Component\Routing\Resolver\RouteCollectionAccessor')
            );
        $this->routeOptionsResolver->expects($this->at(1))
            ->method('resolve')
            ->with(
                $this->identicalTo($loadedRoutes->get('route2')),
                $this->isInstanceOf('Oro\Component\Routing\Resolver\RouteCollectionAccessor')
            );

        $routes = $this->loader->load(null, 'auto');

        $this->assertEquals(
            ['route2', 'route1'],
            array_keys($routes->all())
        );
    }
}
