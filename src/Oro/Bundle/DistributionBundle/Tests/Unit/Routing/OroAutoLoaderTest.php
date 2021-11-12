<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Oro\Bundle\DistributionBundle\Routing\OroAutoLoader;
use Symfony\Component\Routing\Route;

class OroAutoLoaderTest extends AbstractLoaderTest
{
    public function getLoader(): OroAutoLoader
    {
        $loader = new OroAutoLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);
        $loader->setEventDispatcher($this->eventDispatcher);

        return $loader;
    }

    public function getLoaderWithoutEventDispatcher(): OroAutoLoader
    {
        $loader = new OroAutoLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);

        return $loader;
    }

    public function testSupports(): void
    {
        self::assertTrue($this->getLoader()->supports(null, 'oro_auto'));
    }

    public function loadDataProvider(): array
    {
        return [
            [
                [
                    'route2' => (new Route('/root2'))->setOptions(['expose' => true, 'priority' => 10]),
                    'route' => new Route('/root'),
                    'route3' => (new Route('/root3'))->setOptions(['expose' => false, 'priority' => -10])
                ]
            ]
        ];
    }
}
