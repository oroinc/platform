<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeAndRouteVoter;

/**
 * @property ThemeAndRouteVoter voter
 */
class ThemeAndRouteVoterTest extends AbstractPathVoterTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->voter = new ThemeAndRouteVoter($this->themeManager);
    }

    /**
     * @dataProvider rootThemePathsDataProvider
     *
     * @param array $path
     * @param bool  $expectedResult
     */
    public function testRootThemePathVote(array $path, $expectedResult)
    {
        $context = new LayoutContext();
        $context->set('theme', 'black');
        $this->setUpThemeManager(['black' => $this->getThemeMock('black')]);

        $this->voter->setContext($context);
        $this->assertSame($expectedResult, $this->voter->vote($path, ''));
    }

    /**
     * @return array
     */
    public function rootThemePathsDataProvider()
    {
        return [
            'correct path should voted for'                                             => [
                '$path'           => ['black'],
                '$expectedResult' => true
            ],
            'different theme passed, should return null to let other voters do the job' => [
                '$path'           => ['base'],
                '$expectedResult' => null
            ]
        ];
    }

    /**
     * @dataProvider pathsDataProvider
     *
     * @param array $path
     * @param bool  $expectedResult
     */
    public function testThemeHierarchyWithRoutePassed(array $path, $expectedResult)
    {
        $context = new LayoutContext();
        $context->set('theme', 'black');
        $context->set('route_name', 'oro_some_route');
        $this->setUpThemeManager(
            ['black' => $this->getThemeMock('black', 'base'), 'base' => $this->getThemeMock('base')]
        );

        $this->voter->setContext($context);
        $this->assertSame($expectedResult, $this->voter->vote($path, ''));
    }

    /**
     * @return array
     */
    public function pathsDataProvider()
    {
        return [
            'base theme should pass'                             => [
                '$path'           => ['base'],
                '$expectedResult' => true
            ],
            'current theme should pass'                          => [
                '$path'           => ['black'],
                '$expectedResult' => true
            ],
            'base theme with route should pass'                  => [
                '$path'           => ['base', 'oro_some_route'],
                '$expectedResult' => true
            ],
            'current theme with route should pass'               => [
                '$path'           => ['black', 'oro_some_route'],
                '$expectedResult' => true
            ],
            'current theme with different route should not pass' => [
                '$path'           => ['black', 'some_different_rote'],
                '$expectedResult' => null
            ]
        ];
    }
}
