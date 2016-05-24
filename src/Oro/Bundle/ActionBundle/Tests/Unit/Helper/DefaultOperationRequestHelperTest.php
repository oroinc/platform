<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\DefaultOperationRequestHelper;

class DefaultOperationRequestHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelper */
    protected $applicationsHelper;

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

        $this->applicationsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new DefaultOperationRequestHelper($this->requestStack, $this->applicationsHelper);
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

        $this->applicationsHelper->expects($executionRoute ? $this->once() : $this->never())
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
            'exists route name with datagrid' => [
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
        ];
    }
}
