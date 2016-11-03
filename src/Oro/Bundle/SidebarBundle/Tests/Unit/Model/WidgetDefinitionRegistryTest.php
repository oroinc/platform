<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Model;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class WidgetDefinitionRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configurationDataProvider
     * @param array $definitions
     * @param string $placement
     * @param array $expected
     */
    public function testGetWidgetDefinitionsByPlacement(array $definitions, $placement, array $expected)
    {
        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isResourceEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(true);
        $registry = new WidgetDefinitionRegistry($definitions, $featureChecker);
        $actual = $registry->getWidgetDefinitionsByPlacement($placement);
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $actual);
        $this->assertEquals($expected, $actual->toArray());

        $additionalDefinition = ['last' => ['icon' => 'icon.png']];
        $registry->setWidgetDefinitions($additionalDefinition);
        $this->assertEquals(
            array_merge($definitions, $additionalDefinition),
            $registry->getWidgetDefinitions()->toArray()
        );
    }

    /**
     * @return array
     */
    public function configurationDataProvider()
    {
        return array(
            'empty' => array(
                array(),
                'left',
                array()
            ),
            'full left' => array(
                array(
                    'foo' => array(
                        'title' => 'Foo',
                        'icon' => 'foo.ico',
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ),
                    'bar2' => array(
                        'title' => 'Bar2',
                        'icon' => 'bar2.ico',
                        'module' => 'widget/bar2',
                        'placement' => 'right'
                    ),
                ),
                'left',
                array(
                    'foo' => array(
                        'title' => 'Foo',
                        'icon' => 'foo.ico',
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    )
                )
            ),
            'full right' => array(
                array(
                    'foo' => array(
                        'title' => 'Foo',
                        'icon' => 'foo.ico',
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ),
                    'bar2' => array(
                        'title' => 'Bar2',
                        'icon' => 'bar2.ico',
                        'module' => 'widget/bar2',
                        'placement' => 'right'
                    ),
                ),
                'right',
                array(
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ),
                    'bar2' => array(
                        'title' => 'Bar2',
                        'icon' => 'bar2.ico',
                        'module' => 'widget/bar2',
                        'placement' => 'right'
                    ),
                )
            )
        );
    }

    public function testGetWidgetDefinitionsWhenFeatureIsDisabled()
    {
        $definitions = [
            'empty' => [
                [],
                'left',
                []
            ]
        ];

        $featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->setMethods(['isResourceEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($this->anything())
            ->willReturn(false);
        $registry = new WidgetDefinitionRegistry($definitions, $featureChecker);

        $this->assertEquals([], $registry->getWidgetDefinitions()->toArray());
    }
}
