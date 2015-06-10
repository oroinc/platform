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
        return new OroAutoLoader($this->locator, $this->kernel, $this->eventDispatcher);
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
