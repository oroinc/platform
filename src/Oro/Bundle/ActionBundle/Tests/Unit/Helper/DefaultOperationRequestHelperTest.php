<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\DefaultOperationRequestHelper;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DefaultOperationRequestHelperTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private RouteProviderInterface&MockObject $routeProvider;
    private DefaultOperationRequestHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->routeProvider = $this->createMock(RouteProviderInterface::class);

        $this->helper = new DefaultOperationRequestHelper($this->requestStack, $this->routeProvider);
    }

    /**
     * @dataProvider getRequestRouteProvider
     */
    public function testGetRequestRoute(
        ?Request $mainRequest,
        string $executionRoute,
        ?string $expected
    ): void {
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($mainRequest);

        $this->routeProvider->expects($executionRoute ? $this->once() : $this->never())
            ->method('getExecutionRoute')
            ->willReturn($executionRoute);

        $this->assertEquals($expected, $this->helper->getRequestRoute());
    }

    public function getRequestRouteProvider(): array
    {
        return [
            'empty main request' => [
                'mainRequest' => null,
                'executionRoute' => '',
                'expected' => null
            ],
            'empty route name' => [
                'mainRequest' => new Request(),
                'executionRoute' => 'execution_route',
                'expected' => null
            ],
            'execution route name' => [
                'mainRequest' => new Request(['_route' => 'execution_route']),
                'executionRoute' => 'execution_route',
                'expected' => null
            ],
            'exists route name' => [
                'mainRequest' => new Request(['_route' => 'test_route']),
                'executionRoute' => 'execution_route',
                'expected' => 'test_route'
            ],
            'exists route name with datagrid route' => [
                'mainRequest' => new Request([
                    '_route' => DefaultOperationRequestHelper::DATAGRID_ROUTE,
                    'gridName' => 'test-grid',
                    'test-grid' => ['originalRoute' => 'test_original_route']
                ]),
                'executionRoute' => 'test_route',
                'expected' => 'test_original_route'
            ],
            'exists route name with mass action route' => [
                'mainRequest' => new Request([
                    '_route' => DefaultOperationRequestHelper::MASS_ACTION_ROUTE,
                    'gridName' => 'test-grid',
                    'test-grid' => ['originalRoute' => 'test_original_route']
                ]),
                'executionRoute' => 'test_route',
                'expected' => 'test_original_route'
            ],
            'exists route name with datagrid widget route' => [
                'mainRequest' => new Request([
                    '_route' => DefaultOperationRequestHelper::DATAGRID_WIDGET_ROUTE,
                    'gridName' => 'test-grid',
                    'test-grid' => ['originalRoute' => 'test_original_route']
                ]),
                'executionRoute' => 'test_route',
                'expected' => 'test_original_route'
            ],
        ];
    }

    /**
     * @dataProvider isExecutionRouteRequestProvider
     */
    public function isExecutionRouteRequest(
        ?Request $mainRequest,
        ?string $executionRoute,
        ?bool $expected
    ): void {
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($mainRequest);

        $this->routeProvider->expects($executionRoute ? $this->once() : $this->never())
            ->method('getExecutionRoute')
            ->willReturn($executionRoute);

        $this->assertEquals($expected, $this->helper->isExecutionRouteRequest());
    }

    public function isExecutionRouteRequestProvider(): array
    {
        return [
            'empty main request' => [
                'mainRequest' => null,
                'executionRoute' => null,
                'expected' => false
            ],
            'empty route name' => [
                'mainRequest' => new Request(),
                'executionRoute' => 'execution_route',
                'expected' => false
            ],
            'execution route name' => [
                'mainRequest' => new Request(['_route' => 'execution_route']),
                'executionRoute' => 'execution_route',
                'expected' => true
            ],
            'exists route name' => [
                'mainRequest' => new Request(['_route' => 'test_route']),
                'executionRoute' => 'execution_route',
                'expected' => false
            ]
        ];
    }
}
