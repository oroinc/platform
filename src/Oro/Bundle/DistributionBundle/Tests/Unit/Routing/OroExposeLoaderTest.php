<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Oro\Bundle\DistributionBundle\Routing\OroExposeLoader;
use Symfony\Component\Routing\Route;

class OroExposeLoaderTest extends AbstractLoaderTest
{
    /**
     * {@inheritdoc}
     */
    public function getLoader()
    {
        $loader = new OroExposeLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);
        $loader->setEventDispatcher($this->eventDispatcher);

        return $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoaderWithoutEventDispatcher()
    {
        $loader = new OroExposeLoader($this->kernel, $this->routeOptionsResolver);
        $loader->setResolver($this->loaderResolver);

        return $loader;
    }

    public function testSupports()
    {
        $this->assertTrue($this->getLoader()->supports(null, 'oro_expose'));
    }

    /**
     * {@inheritdoc}
     */
    public function loadDataProvider()
    {
        return [
            [
                ['route2' => (new Route('/root2'))->setOption('expose', true)]
            ]
        ];
    }
}
