<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Configuration;

use Oro\Bundle\SidebarBundle\Configuration\WidgetDefinitionProvider;
use Oro\Bundle\SidebarBundle\Tests\Unit\Fixtures\BarBundle\BarBundle;
use Oro\Bundle\SidebarBundle\Tests\Unit\Fixtures\FooBundle\FooBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class WidgetDefinitionProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private WidgetDefinitionProvider $widgetDefinitionProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('WidgetDefinitionProvider');

        $this->widgetDefinitionProvider = new WidgetDefinitionProvider($cacheFile, false);

        $bundle1 = new FooBundle();
        $bundle2 = new BarBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);
    }

    public function testGetWidgetDefinitionsByPlacementForLeftPlacement()
    {
        self::assertEquals(
            [
                'foo'  => [
                    'title'             => 'Foo',
                    'icon'              => 'foo.ico',
                    'iconClass'         => null,
                    'dialogIcon'        => 'foo-dialog.png',
                    'module'            => 'widget/foo',
                    'cssClass'          => 'foo-css-class',
                    'description'       => 'foo.description',
                    'placement'         => 'left',
                    'showRefreshButton' => false,
                    'settings'          => [
                        'test' => 'Hello'
                    ],
                    'isNew'             => true
                ],
                'foo1' => [
                    'title'             => 'Foo1',
                    'icon'              => 'foo1.ico',
                    'iconClass'         => null,
                    'placement'         => 'both',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ],
                'bar'  => [
                    'title'             => 'Bar',
                    'icon'              => null,
                    'iconClass'         => 'test',
                    'module'            => 'widget/bar',
                    'placement'         => 'both',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ]
            ],
            $this->widgetDefinitionProvider->getWidgetDefinitionsByPlacement('left')
        );
    }

    public function testGetWidgetDefinitionsByPlacementForRightPlacement()
    {
        self::assertEquals(
            [
                'foo1' => [
                    'title'             => 'Foo1',
                    'icon'              => 'foo1.ico',
                    'iconClass'         => null,
                    'placement'         => 'both',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ],
                'foo2' => [
                    'title'             => 'Foo2 Overrided',
                    'icon'              => null,
                    'iconClass'         => 'foo2-icon-class',
                    'placement'         => 'right',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ],
                'bar'  => [
                    'title'             => 'Bar',
                    'icon'              => null,
                    'iconClass'         => 'test',
                    'module'            => 'widget/bar',
                    'placement'         => 'both',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ],
                'bar2' => [
                    'title'             => 'Bar2',
                    'icon'              => 'bar2.ico',
                    'iconClass'         => null,
                    'module'            => 'widget/bar2',
                    'placement'         => 'right',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ]
            ],
            $this->widgetDefinitionProvider->getWidgetDefinitionsByPlacement('right')
        );
    }

    public function testGetWidgetDefinitionsByPlacementForBothPlacements()
    {
        self::assertEquals(
            [
                'foo1' => [
                    'title'             => 'Foo1',
                    'icon'              => 'foo1.ico',
                    'iconClass'         => null,
                    'placement'         => 'both',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ],
                'bar'  => [
                    'title'             => 'Bar',
                    'icon'              => null,
                    'iconClass'         => 'test',
                    'module'            => 'widget/bar',
                    'placement'         => 'both',
                    'showRefreshButton' => true,
                    'settings'          => null,
                    'isNew'             => false
                ]
            ],
            $this->widgetDefinitionProvider->getWidgetDefinitionsByPlacement('both')
        );
    }
}
