<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\NavigationBundle\Layout\DataProvider\NavigationTitleProvider;

class NavigationTitleProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var NavigationTitleProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TitleService
     */
    private $titleService;

    public function setUp()
    {
        $this->titleService = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new NavigationTitleProvider($this->titleService);
    }


    /**
     * @dataProvider getDataDataProvider
     * @param string $routeName
     * @param array  $params
     * @param string $expected
     */
    public function testGetTitle($routeName, $params, $expected)
    {
        $this->titleService->expects($this->once())
            ->method('loadByRoute')
            ->willReturn(null);

        $this->titleService->expects($this->once())
            ->method('setParams')
            ->with([])
            ->willReturn($this->titleService);

        $this->titleService->expects($this->once())
            ->method('render')
            ->with([], null, null, null, true)
            ->willReturn($expected);

        $result = $this->provider->getTitle($routeName, $params);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            'existRoute' => [
                'routeName' => 'oro_frontend_root',
                'params' => [],
                'Home Page'
            ],
            'nonExistRoute' => [
                'routeName' => 'non_exist_route',
                'params' => [],
                'non_exist_route'
            ],
        ];
    }
}
