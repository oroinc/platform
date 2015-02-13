<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Theme;

use Oro\Bundle\LayoutBundle\Model\Theme;
use Oro\Bundle\LayoutBundle\Theme\ThemeFactory;

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
        $minimalDefinition->setHidden(true);

        $fullDefinition = new Theme('oro-black', 'base');
        $fullDefinition->setHidden(false);
        $fullDefinition->setLogo('oro-black-logo.png');
        $fullDefinition->setScreenshot('oro-black-screenshot.png');
        $fullDefinition->setLabel('Oro Black Theme');
        $fullDefinition->setDirectory('OroBlack');

        return [
            'minimal definition given' => [
                '$name'           => 'base',
                '$definition'     => ['parent' => null, 'hidden' => true],
                '$expectedResult' => $minimalDefinition,
            ],
            'full definition given'    => [
                '$name'           => 'oro-black',
                '$definition'     => [
                    'parent'     => 'base',
                    'hidden'     => false,
                    'label'      => 'Oro Black Theme',
                    'screenshot' => 'oro-black-screenshot.png',
                    'logo'       => 'oro-black-logo.png',
                    'directory'  => 'OroBlack'
                ],
                '$expectedResult' => $fullDefinition,
            ]
        ];
    }
}
