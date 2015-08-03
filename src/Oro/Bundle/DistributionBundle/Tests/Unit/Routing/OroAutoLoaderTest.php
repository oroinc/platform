<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Symfony\Component\Routing\Route;

use Oro\Bundle\DistributionBundle\Routing\OroAutoLoader;

class OroAutoLoaderTest extends AbstractLoaderTest
{
    /**
     * {@inheritdoc}
     */
    public function getLoader()
    {
        $loader = new OroAutoLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);
        $loader->setEventDispatcher($this->eventDispatcher);

        return $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoaderWithoutEventDispatcher()
    {
        $loader = new OroAutoLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);

        return $loader;
    }

    public function testSupports()
    {
        $this->assertTrue($this->getLoader()->supports(null, 'oro_auto'));
    }

    /**
     * {@inheritdoc}
     */
    public function loadDataProvider()
    {
        return [
            [
                [
                    'route' => new Route('/root'),
                    'route2' => (new Route('/root2'))->setOption('expose', true),
                    'route3' => (new Route('/root3'))->setOption('expose', false)
                ]
            ]
        ];
    }
}
