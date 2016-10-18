<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;

class DatagridRouteHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var DatagridRouteHelper */
    protected $routeHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = $this->getMock(RouterInterface::class);

        $this->helper = new DatagridRouteHelper($this->router);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->router, $this->helper);
    }

    public function testGenerate()
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'routeName',
                ['grid' => ['gridName' => http_build_query(['param1' => 'value1'])]],
                RouterInterface::ABSOLUTE_URL
            )
            ->willReturn('generatedValue');

        $this->assertEquals(
            'generatedValue',
            $this->helper->generate('routeName', 'gridName', ['param1' => 'value1'], RouterInterface::ABSOLUTE_URL)
        );
    }
}
