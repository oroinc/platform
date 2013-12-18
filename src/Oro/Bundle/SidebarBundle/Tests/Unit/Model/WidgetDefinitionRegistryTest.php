<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Model;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class WidgetDefinitionRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider configurationDataProvider
     * @param array $definitions
     * @param string $placement
     * @param array $expected
     */
    public function testGetWidgetDefinitionsByPlacement(
        array $definitions,
        $placement,
        array $expected
    ) {
        $assetHelper = $this->getAssetHelper();
        $assetHelper->expects($this->exactly(count($definitions)))
            ->method('getUrl')
            ->will($this->returnValue('ASSET'));

        $registry = new WidgetDefinitionRegistry($definitions, $assetHelper);
        $actual = $registry->getWidgetDefinitionsByPlacement($placement);
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $actual);
        $this->assertEquals($expected, $actual->toArray());
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
                        'icon' => 'ASSET',
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => 'ASSET',
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
                        'icon' => 'ASSET',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ),
                    'bar2' => array(
                        'title' => 'Bar2',
                        'icon' => 'ASSET',
                        'module' => 'widget/bar2',
                        'placement' => 'right'
                    ),
                )
            )
        );
    }

    protected function getAssetHelper()
    {
        return $this->getMockBuilder('Symfony\Component\Templating\Asset\PackageInterface')
            ->getMock();
    }
}
