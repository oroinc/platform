<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Symfony\Component\Routing\Route;

use Oro\Bundle\DistributionBundle\Routing\OroExposeLoader;

class OroExposeLoaderTest extends AbstractLoaderTest
{
    /**
     * {@inheritdoc}
     */
    public function getLoader()
    {
        return new OroExposeLoader($this->locator, $this->kernel, $this->eventDispatcher);
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
