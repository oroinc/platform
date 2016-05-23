<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActionBundle\Helper\RequestHelper;

class RequestHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var RequestHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new RequestHelper($this->requestStack);
    }

    /**
     * @param Request $parentRequest
     * @param Request $masterRequest
     * @param string|null $expected
     *
     * @dataProvider getRequestRouteProvider
     */
    public function testGetRequestRoute(Request $parentRequest = null, Request $masterRequest = null, $expected = null)
    {
        $this->requestStack->expects($this->at(0))
            ->method('getParentRequest')
            ->willReturn($parentRequest);

        $this->requestStack->expects($parentRequest ? $this->at(1) : $this->never())
            ->method('getMasterRequest')
            ->willReturn($masterRequest);

        $this->assertEquals($expected, $this->helper->getRequestRoute());
    }

    /**
     * @return array
     */
    public function getRequestRouteProvider()
    {
        return [
            'empty parent request' => [
                'parentRequest' => null,
                'masterRequest' => null,
                'expected' => null,
            ],
            'empty master request' => [
                'parentRequest' => new Request(),
                'masterRequest' => null,
                'expected' => null,
            ],
            'empty route name' => [
                'parentRequest' => new Request(),
                'masterRequest' => new Request(),
                'expected' => null,
            ],
            'exists route name' => [
                'parentRequest' => new Request(),
                'masterRequest' => new Request([
                    '_route' => 'test_route',
                ]),
                'expected' => 'test_route',
            ]
        ];
    }
}
