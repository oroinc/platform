<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
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
     * @param array  $params
     * @param string $title
     * @param string $expected
     */
    public function testGetTitle($params, $title, $expected)
    {
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

        $result = $this->provider->getTitle($params);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            'existRoute' => [
                'params' => [],
                'title' => 'Home Page',
                'expected' => 'Home Page'

            ],
            'existRouteWithSuffix' => [
                'params' => [],
                'title' => '- Home Page',
                'expected' => 'Home Page'
            ]
        ];
    }
}
