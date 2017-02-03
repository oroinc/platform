<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Manager;

use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class PageTemplatesManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeManager|\PHPUnit_Framework_MockObject_MockObject */
    private $themeManagerMock;

    /** @var PageTemplatesManager */
    private $pageTemplatesManager;

    protected function setUp()
    {
        $this->themeManagerMock = $this->getMockBuilder(ThemeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTemplatesManager = new PageTemplatesManager($this->themeManagerMock);
    }

    public function testGetRoutePageTemplates()
    {
        $theme1 = new Theme('Theme1');
        $theme1->addPageTemplate(new PageTemplate('Page template 1', 'some_key1', 'route_name_1'));
        $theme1->addPageTemplateTitle('route_name_1', 'Route title 1');
        $theme2 = new Theme('Theme2');
        $theme2->addPageTemplate(new PageTemplate('Page template 2', 'some_key2', 'route_name_1'));
        $theme2->addPageTemplateTitle('route_name_1', 'Route title 2');
        $theme3 = new Theme('Theme3');
        $theme3->addPageTemplate(new PageTemplate('Page template 3', 'some_key3', 'route_name_with_no_title_defined'));

        $this->themeManagerMock->expects($this->once())
            ->method('getAllThemes')
            ->willReturn([$theme1, $theme2, $theme3]);

        $expected = [
            'route_name_1' => [
                'label' => 'Route title 2',
                'choices' => [
                    'some_key1' => 'Page template 1',
                    'some_key2' => 'Page template 2'
                ]
            ],
            'route_name_with_no_title_defined' => [
                'label' => 'route_name_with_no_title_defined',
                'choices' => [
                    'some_key3' => 'Page template 3',
                ]
            ]
        ];
        $result = $this->pageTemplatesManager->getRoutePageTemplates();

        $this->assertEquals($expected, $result);
    }
}
