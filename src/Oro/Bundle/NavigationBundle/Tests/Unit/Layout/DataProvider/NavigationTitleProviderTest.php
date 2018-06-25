<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Layout\DataProvider\NavigationTitleProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleService;

class NavigationTitleProviderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var NavigationTitleProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TitleService
     */
    private $titleService;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    private $userConfigManager;

    public function setUp()
    {
        $this->titleService = $this->getMockBuilder(TitleService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userConfigManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new NavigationTitleProvider($this->titleService, $this->userConfigManager);
    }


    /**
     * @dataProvider getDataDataProvider
     *
     * @param string $routeName
     * @param array  $params
     * @param string $title
     * @param string $expected
     */
    public function testGetTitle($routeName, $params, $title, $expected)
    {
        $this->titleService->expects($this->once())
            ->method('loadByRoute')
            ->with($routeName, 'frontend_menu')
            ->willReturn(null);

        $this->titleService->expects($this->once())
            ->method('setParams')
            ->with([])
            ->willReturn($this->titleService);

        $this->titleService->expects($this->once())
            ->method('render')
            ->with([], null, null, null, true)
            ->willReturn($title);

        $this->userConfigManager->expects($this->once())
            ->method('get')
            ->with('oro_navigation.title_delimiter')
            ->willReturn('-');

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
                'title' => 'Home Page',
                'expected' => 'Home Page'

            ],
            'existRouteWithSuffix' => [
                'routeName' => 'oro_frontend_root',
                'params' => [],
                'title' => '- Home Page',
                'expected' => 'Home Page'
            ]
        ];
    }
}
