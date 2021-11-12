<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Oro\Bundle\DistributionBundle\Routing\OroExposeLoader;
use Symfony\Component\Routing\Route;

class OroExposeLoaderTest extends AbstractLoaderTest
{
    public function getLoader(): OroExposeLoader
    {
        $loader = new OroExposeLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);
        $loader->setEventDispatcher($this->eventDispatcher);

        return $loader;
    }

    public function getLoaderWithoutEventDispatcher(): OroExposeLoader
    {
        $loader = new OroExposeLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);

        return $loader;
    }

    public function testSupports(): void
    {
        self::assertTrue($this->getLoader()->supports(null, 'oro_expose'));
    }

    public function loadDataProvider(): array
    {
        return [
            [
                ['route2' => (new Route('/root2'))->setOptions(['expose' => true, 'priority' => 10])]
            ]
        ];
    }
}
