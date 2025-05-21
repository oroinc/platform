<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
use PHPUnit\Framework\TestCase;

final class ThemeFactoryTest extends TestCase
{
    private ThemeFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new ThemeFactory(PropertyAccess::createPropertyAccessor());
    }

    /**
     * @dataProvider themeDefinitionDataProvider
     */
    public function testCreate(string $name, array $definition, mixed $expectedResult): void
    {
        $result = $this->factory->create($name, $definition);

        self::assertEquals($expectedResult, $result);
    }

    public function themeDefinitionDataProvider(): array
    {
        $minimalDefinition = new Theme('base');

        $fullDefinition = new Theme('oro-black', 'base');
        $fullDefinition->setIcon('oro-black-icon.ico');
        $fullDefinition->setLogo('oro-black-logo.png');
        $fullDefinition->setLogoSmall('oro-black-logo-small.png');
        $fullDefinition->setImagePlaceholders(['no_image' => 'some/test/route.png']);
        $fullDefinition->setRtlSupport(true);
        $fullDefinition->setSvgIconsSupport(true);
        $fullDefinition->setScreenshot('oro-black-screenshot.png');
        $fullDefinition->setLabel('Oro Black Theme');
        $fullDefinition->setDirectory('OroBlack');
        $fullDefinition->setGroups(['main', 'frontend']);
        $fullDefinition->setDescription('description');
        $fullDefinition->setFonts(['test' => 'font']);

        $config = [
            'key' => 'value',
            'page_templates' => [
                'templates' => [
                    [
                        'label' => 'Some label',
                        'key' => 'some_key',
                        'route_name' => 'some_route_name',
                        'screenshot' => 'some_screenshot',
                        'description' => 'Some description'
                    ],
                    [
                        'label' => 'Some label (disabled)',
                        'key' => 'some_key_disabled',
                        'route_name' => 'some_route_name_disabled',
                        'enabled' => false,
                    ]
                ],
                'titles' => [
                    'some_route_name' => 'Title for some route name'
                ]
            ],
        ];

        $fullDefinition->setConfig($config);

        $fullDefinition->addPageTemplateTitle('some_route_name', 'Title for some route name');

        $pageTemplate = new PageTemplate('Some label', 'some_key', 'some_route_name');
        $pageTemplate->setDescription('Some description')
            ->setScreenshot('some_screenshot');
        $fullDefinition->addPageTemplate($pageTemplate);

        $pageTemplate = new PageTemplate('Some label (disabled)', 'some_key_disabled', 'some_route_name_disabled');
        $pageTemplate->setEnabled(false);
        $fullDefinition->addPageTemplate($pageTemplate);

        return [
            'minimal definition given' => [
                '$name'           => 'base',
                '$definition'     => [],
                '$expectedResult' => $minimalDefinition,
            ],
            'full definition given'    => [
                '$name'           => 'oro-black',
                '$definition'     => [
                    'parent'     => 'base',
                    'groups'     => ['main', 'frontend'],
                    'label'      => 'Oro Black Theme',
                    'screenshot' => 'oro-black-screenshot.png',
                    'icon'       => 'oro-black-icon.ico',
                    'logo'       => 'oro-black-logo.png',
                    'logo_small' => 'oro-black-logo-small.png',
                    'image_placeholders' => ['no_image'   => 'some/test/route.png'],
                    'rtl_support' => true,
                    'svg_icons_support' => true,
                    'directory'  => 'OroBlack',
                    'description' => 'description',
                    'fonts' => ['test' => 'font'],
                    'config' => $config
                ],
                '$expectedResult' => $fullDefinition,
            ]
        ];
    }
}
