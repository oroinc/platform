<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Manager;

use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageTemplatesManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $themeManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var PageTemplatesManager */
    private $pageTemplatesManager;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->pageTemplatesManager = new PageTemplatesManager($this->themeManager, $this->translator);
    }

    /**
     * @dataProvider routePageTemplatesDataProvider
     */
    public function testGetRoutePageTemplates(array $themes, array $expected)
    {
        $this->themeManager->expects($this->once())
            ->method('getAllThemes')
            ->willReturn($themes);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnArgument(0);

        $this->assertEquals($expected, $this->pageTemplatesManager->getRoutePageTemplates());
    }

    public function routePageTemplatesDataProvider(): array
    {
        return [
            'with title' => [
                'themes' => [
                    $this->getTheme('Theme1', [
                        $this->getPageTemplate('Page Template 1', 'some_key1_1', 'route_name_1', 'description_1'),
                    ], [
                        'route_name_1' => 'Route Title 1',
                    ]),
                    $this->getTheme('Theme2', [
                        $this->getPageTemplate('Page Template 2', 'some_key2_1', 'route_name_2', 'description_2'),
                    ], [
                        'route_name_2' => 'Route Title 2',
                    ]),
                ],
                'expected' => [
                    'route_name_1' => [
                        'label' => 'Route Title 1',
                        'descriptions' => [
                            'some_key1_1' => 'description_1',
                        ],
                        'choices' => [
                            'some_key1_1' => 'Page Template 1',
                        ]
                    ],
                    'route_name_2' => [
                        'label' => 'Route Title 2',
                        'descriptions' => [
                            'some_key2_1' => 'description_2',
                        ],
                        'choices' => [
                            'some_key2_1' => 'Page Template 2',
                        ]
                    ],
                ]
            ],
            'with title overriding' => [
                'themes' => [
                    $this->getTheme('Theme1', [
                        $this->getPageTemplate('Page Template 1', 'some_key1_1', 'route_name_1', 'description_1'),
                    ], [
                        'route_name_1' => 'Route Title 1',
                    ]),
                    $this->getTheme('Theme2', [
                        $this->getPageTemplate('Page Template 2', 'some_key2_1', 'route_name_1', 'description_2'),
                    ], [
                        'route_name_1' => 'New Route Title 1',
                    ]),
                ],
                'expected' => [
                    'route_name_1' => [
                        'label' => 'New Route Title 1',
                        'descriptions' => [
                            'some_key1_1' => 'description_1',
                            'some_key2_1' => 'description_2',
                        ],
                        'choices' => [
                            'some_key1_1' => 'Page Template 1',
                            'some_key2_1' => 'Page Template 2',
                        ]
                    ],
                ]
            ],
            'without title and with description' => [
                'themes' => [
                    $this->getTheme('Theme1', [
                        $this->getPageTemplate('Page Template 1', 'some_key1_1', 'route_name_1', 'description_1'),
                    ]),
                    $this->getTheme('Theme2', [
                        $this->getPageTemplate('Page Template 2', 'some_key2_1', 'route_name_1', 'description_2'),
                    ]),
                ],
                'expected' => [
                    'route_name_1' => [
                        'label' => 'route_name_1',
                        'descriptions' => [
                            'some_key1_1' => 'description_1',
                            'some_key2_1' => 'description_2',
                        ],
                        'choices' => [
                            'some_key1_1' => 'Page Template 1',
                            'some_key2_1' => 'Page Template 2',
                        ]
                    ]
                ]
            ],
        ];
    }

    private function getPageTemplate(string $label, string $key, string $routeName, ?string $description): PageTemplate
    {
        $pageTemplate = new PageTemplate($label, $key, $routeName);
        $pageTemplate->setDescription($description);

        return $pageTemplate;
    }

    /**
     * @param string         $themeName
     * @param PageTemplate[] $pageTemplates
     * @param array          $pageTemplateTitles
     *
     * @return Theme
     */
    private function getTheme(string $themeName, array $pageTemplates, array $pageTemplateTitles = []): Theme
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
