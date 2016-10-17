<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class DatagridRouteHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var Router|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var DatagridRouteHelper */
    protected $routeHelper;

    protected function setUp()
    {
        $this->router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeHelper = new DatagridRouteHelper($this->router);
    }

    protected function tearDown()
    {
        unset($this->router);
    }

    public function testGenerate()
    {
        $this->router->expects($this->atLeastOnce())->method('generate');
        $this->routeHelper->generate(uniqid("", true), uniqid("", true));
    }
}
