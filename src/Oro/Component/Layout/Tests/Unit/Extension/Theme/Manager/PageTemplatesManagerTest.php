<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Manager;

use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class PageTemplatesManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
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

    /**
     * @dataProvider routePageTemplatesDataProvider
     *
     * @param array $themes
     * @param array $expected
     */
    public function testGetRoutePageTemplates(array $themes, array $expected)
    {
        $this->themeManagerMock->expects($this->once())
            ->method('getAllThemes')
            ->willReturn($themes);

        $this->assertEquals($expected, $this->pageTemplatesManager->getRoutePageTemplates());
    }

    /**
     * @return array
     */
    public function routePageTemplatesDataProvider()
    {
        return [
            'with title' => [
                'themes' => [
                    $this->getTheme('Theme1', [
                        new PageTemplate('Page Template 1', 'some_key1_1', 'route_name_1'),
                    ], [
                        'route_name_1' => 'Route Title 1',
                    ]),
                    $this->getTheme('Theme2', [
                        new PageTemplate('Page Template 2', 'some_key2_1', 'route_name_2'),
                    ], [
                        'route_name_2' => 'Route Title 2',
                    ]),
                ],
                'expected' => [
                    'route_name_1' => [
                        'label' => 'Route Title 1',
                        'choices' => [
                            'Page Template 1' => 'some_key1_1',
                        ]
                    ],
                    'route_name_2' => [
                        'label' => 'Route Title 2',
                        'choices' => [
                            'Page Template 2' => 'some_key2_1',
                        ]
                    ],
                ]
            ],
            'with title overriding' => [
                'themes' => [
                    $this->getTheme('Theme1', [
                        new PageTemplate('Page Template 1', 'some_key1_1', 'route_name_1'),
                    ], [
                        'route_name_1' => 'Route Title 1',
                    ]),
                    $this->getTheme('Theme2', [
                        new PageTemplate('Page Template 2', 'some_key2_1', 'route_name_1'),
                    ], [
                        'route_name_1' => 'New Route Title 1',
                    ]),
                ],
                'expected' => [
                    'route_name_1' => [
                        'label' => 'New Route Title 1',
                        'choices' => [
                            'Page Template 1' => 'some_key1_1',
                            'Page Template 2' => 'some_key2_1',
                        ]
                    ],
                ]
            ],
            'without title' => [
                'themes' => [
                    $this->getTheme('Theme1', [
                        new PageTemplate('Page Template 1', 'some_key1_1', 'route_name_1'),
                    ])
                ],
                'expected' => [
                    'route_name_1' => [
                        'label' => 'route_name_1',
                        'choices' => [
                            'Page Template 1' => 'some_key1_1',
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @param string         $themeName
     * @param PageTemplate[] $pageTemplates
     * @param array          $pageTemplateTitles
     *
     * @return Theme
     */
    private function getTheme($themeName, array $pageTemplates, array $pageTemplateTitles = [])
    {
        $theme = new Theme($themeName);

        foreach ($pageTemplates as $pageTemplate) {
            $theme->addPageTemplate($pageTemplate);
        }

        foreach ($pageTemplateTitles as $key => $value) {
            $theme->addPageTemplateTitle($key, $value);
        }

        return $theme;
    }
}
