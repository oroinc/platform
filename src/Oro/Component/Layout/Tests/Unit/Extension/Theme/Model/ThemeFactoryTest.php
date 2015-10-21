<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Model;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;

class ThemeFactoryTest extends \PHPUnit_Framework_TestCase
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
                    'description' => 'description'
                ],
                '$expectedResult' => $fullDefinition,
            ]
        ];
    }
}
