<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Symfony\Component\Routing\RouteCollection;

use Oro\Bundle\DistributionBundle\Routing\SharedData;

class SharedDataTest extends \PHPUnit_Framework_TestCase
{
    public function testRoutes()
    {
        $sharedData = new SharedData();

        $routes1 = new RouteCollection();
        $routes2 = new RouteCollection();
        $sharedData->setRoutes('res1', $routes1);
        $sharedData->setRoutes('res2', $routes2);

        $this->assertSame($routes1, $sharedData->getRoutes('res1'));
        $this->assertSame($routes2, $sharedData->getRoutes('res2'));
        $this->assertNull($sharedData->getRoutes('another'));
    }
}
