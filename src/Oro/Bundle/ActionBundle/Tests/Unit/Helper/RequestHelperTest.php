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
     * @param Request|null $request
     * @param string|null $expected
     *
     * @dataProvider getMasterRequestRouteProvider
     */
    public function testGetMasterRequestRoute($request, $expected)
    {
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->assertEquals($expected, $this->helper->getMasterRequestRoute());
    }

    /**
     * @return array
     */
    public function getMasterRequestRouteProvider()
    {
        return [
            'empty request' => [
                'request' => null,
                'expected' => null,
            ],
            'empty route name' => [
                'request' => new Request(),
                'expected' => null,
            ],
            'exists route name' => [
                'request' => new Request([
                    '_route' => 'test_route',
                ]),
                'expected' => 'test_route',
            ]
        ];
    }
}
