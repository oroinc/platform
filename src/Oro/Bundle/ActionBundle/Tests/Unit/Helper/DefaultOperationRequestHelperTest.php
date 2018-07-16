<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\DefaultOperationRequestHelper;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DefaultOperationRequestHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestStack */
    protected $requestStack;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RouteProviderInterface */
    protected $routeProvider;

    /** @var DefaultOperationRequestHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeProvider = $this->getMockBuilder(RouteProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new DefaultOperationRequestHelper($this->requestStack, $this->routeProvider);
    }

    /**
     * @param Request $masterRequest
     * @param string|null $executionRoute
     * @param string|null $expected
     *
     * @dataProvider getRequestRouteProvider
     */
    public function testGetRequestRoute(Request $masterRequest = null, $executionRoute = null, $expected = null)
    {
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($masterRequest);

        $this->routeProvider->expects($executionRoute ? $this->once() : $this->never())
            ->method('getExecutionRoute')
            ->willReturn($executionRoute);

        $this->assertEquals($expected, $this->helper->getRequestRoute());
    }

    /**
     * @return array
     */
    public function getRequestRouteProvider()
    {
        return [
            'empty master request' => [
                'masterRequest' => null,
                'executionRoute' => null,
                'expected' => null,
            ],
            'empty route name' => [
                'masterRequest' => new Request(),
                'executionRoute' => 'execution_route',
                'expected' => null,
            ],
            'execution route name' => [
                'masterRequest' => new Request([
                    '_route' => 'execution_route',
                ]),
                'executionRoute' => 'execution_route',
                'expected' => null,
            ],
            'exists route name' => [
                'masterRequest' => new Request([
                    '_route' => 'test_route',
                ]),
                'executionRoute' => 'execution_route',
                'expected' => 'test_route',
            ],
            'exists route name with datagrid route' => [
                'masterRequest' => new Request([
                    '_route' => DefaultOperationRequestHelper::DATAGRID_ROUTE,
                    'gridName' => 'test-grid',
                    'test-grid' => [
                        'originalRoute' => 'test_original_route'
                    ]
                ]),
                'executionRoute' => 'test_route',
                'expected' => 'test_original_route',
            ],
            'exists route name with mass action route' => [
                'masterRequest' => new Request([
                    '_route' => DefaultOperationRequestHelper::MASS_ACTION_ROUTE,
                    'gridName' => 'test-grid',
                    'test-grid' => [
                        'originalRoute' => 'test_original_route'
                    ]
                ]),
                'executionRoute' => 'test_route',
                'expected' => 'test_original_route',
            ],
            'exists route name with datagrid widget route' => [
                'masterRequest' => new Request(
                    [
                        '_route' => DefaultOperationRequestHelper::DATAGRID_WIDGET_ROUTE,
                        'gridName' => 'test-grid',
                        'test-grid' => [
                            'originalRoute' => 'test_original_route',
                        ],
                    ]
                ),
                'executionRoute' => 'test_route',
                'expected' => 'test_original_route',
            ],
        ];
    }

    /**
     * @param Request $masterRequest
     * @param string|null $executionRoute
     * @param bool $expected
     *
     * @dataProvider isExecutionRouteRequestProvider
     */
    public function isExecutionRouteRequest(Request $masterRequest = null, $executionRoute = null, $expected = null)
    {
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($masterRequest);

        $this->routeProvider->expects($executionRoute ? $this->once() : $this->never())
            ->method('getExecutionRoute')
            ->willReturn($executionRoute);

        $this->assertEquals($expected, $this->helper->isExecutionRouteRequest());
    }

    /**
     * @return array
     */
    public function isExecutionRouteRequestProvider()
    {
        return [
            'empty master request' => [
                'masterRequest' => null,
                'executionRoute' => null,
                'expected' => false,
            ],
            'empty route name' => [
                'masterRequest' => new Request(),
                'executionRoute' => 'execution_route',
                'expected' => false,
            ],
            'execution route name' => [
                'masterRequest' => new Request([
                    '_route' => 'execution_route',
                ]),
                'executionRoute' => 'execution_route',
                'expected' => true,
            ],
            'exists route name' => [
                'masterRequest' => new Request([
                    '_route' => 'test_route',
                ]),
                'executionRoute' => 'execution_route',
                'expected' => false,
            ]
        ];
    }
}
