<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;

class ThemeFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeFactory */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new ThemeFactory();
    }

    protected function tearDown()
    {
        unset($this->factory);
    }

    /**
     * @dataProvider themeDefinitionDataProvider
     *
     * @param string $name
     * @param array  $definition
     * @param mixed  $expectedResult
     */
    public function testCreate($name, array $definition, $expectedResult)
    {
        $result = $this->factory->create($name, $definition);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function themeDefinitionDataProvider()
    {
        $minimalDefinition = new Theme('base');

        $fullDefinition = new Theme('oro-black', 'base');
        $fullDefinition->setIcon('oro-black-icon.ico');
        $fullDefinition->setLogo('oro-black-logo.png');
        $fullDefinition->setScreenshot('oro-black-screenshot.png');
        $fullDefinition->setLabel('Oro Black Theme');
        $fullDefinition->setDirectory('OroBlack');
        $fullDefinition->setGroups(['main', 'frontend']);
        $fullDefinition->setDescription('description');

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
            ]
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
                    'directory'  => 'OroBlack',
                    'description' => 'description',
                    'config' => $config
                ],
                '$expectedResult' => $fullDefinition,
            ]
        ];
    }
}
