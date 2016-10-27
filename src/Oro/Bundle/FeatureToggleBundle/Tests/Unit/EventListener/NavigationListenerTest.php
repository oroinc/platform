<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\EventListener;

use Knp\Menu\MenuItem;

use Oro\Bundle\FeatureToggleBundle\EventListener\NavigationListener;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;

class NavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var NavigationListener */
    protected $navigationListener;

    public function setUp()
    {
        $featureChecker = $this->getMockBuilder('Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker')
            ->disableOriginalConstructor()
            ->getMock();
        $featureChecker->expects($this->any())
            ->method('getDisabledResourcesByType')
            ->will($this->returnValueMap([
                [
                    'navigation_items',
                    [
                        'root.child2.disabled',
                        'root.child2_2.disabled',
                        'root.child3.disabled',
                        'root.child3.disabled2',
                        'root.child3_2.disabled',
                        'root.child3_2.disabled2',
                    ],
                ],
            ]));

        $this->navigationListener = new NavigationListener($featureChecker);
    }

    /**
     * @dataProvider onNavigationConfigureProvider
     */
    public function testOnNavigationConfigure(ConfigureMenuEvent $event, MenuItem $expected)
    {
        $this->navigationListener->onNavigationConfigure($event);
        $this->assertEquals($expected, $event->getMenu());
    }

    public function onNavigationConfigureProvider()
    {
        $factory = $this->getMock('Knp\Menu\FactoryInterface');
        
        return [
            [
                new ConfigureMenuEvent(
                    $factory,
                    $this->addChildren(
                        new MenuItem('root', $factory),
                        [
                            new MenuItem('child1', $factory),
                            // unclickable menu with enabled item
                            $this->addChildren(
                                (new MenuItem('child2', $factory))
                                    ->setUri('#'),
                                [
                                    new MenuItem('disabled', $factory),
                                    new MenuItem('enabled', $factory),
                                ]
                            ),
                            // clickable menu with enabled item
                            $this->addChildren(
                                new MenuItem('child2_2', $factory),
                                [
                                    new MenuItem('disabled', $factory),
                                    new MenuItem('enabled', $factory),
                                ]
                            ),
                            // unclickable menu without enabled item
                            $this->addChildren(
                                (new MenuItem('child3', $factory))
                                    ->setUri('#'),
                                [
                                    new MenuItem('disabled', $factory),
                                    new MenuItem('disabled2', $factory),
                                ]
                            ),
                            // clickable menu without enabled item
                            $this->addChildren(
                                new MenuItem('child3_2', $factory),
                                [
                                    new MenuItem('disabled', $factory),
                                    new MenuItem('disabled2', $factory),
                                ]
                            )
                        ]
                    )
                ),
                $this->addChildren(
                    new MenuItem('root', $factory),
                    [
                        new MenuItem('child1', $factory),
                        // unclickable menu with enabled item
                        $this->addChildren(
                            (new MenuItem('child2', $factory))
                                ->setUri('#'),
                            [
                                (new MenuItem('disabled', $factory))
                                    ->setDisplay(false),
                                new MenuItem('enabled', $factory),
                            ]
                        ),
                        // clickable menu with enabled item
                        $this->addChildren(
                            new MenuItem('child2_2', $factory),
                            [
                                (new MenuItem('disabled', $factory))
                                    ->setDisplay(false),
                                new MenuItem('enabled', $factory),
                            ]
                        ),
                        // unclickable menu without enabled item
                        $this->addChildren(
                            (new MenuItem('child3', $factory))
                                ->setUri('#')
                                ->setDisplay(false),
                            [
                                (new MenuItem('disabled', $factory))
                                    ->setDisplay(false),
                                (new MenuItem('disabled2', $factory))
                                    ->setDisplay(false),
                            ]
                        ),
                        // clickable menu without enabled item
                        $this->addChildren(
                            new MenuItem('child3_2', $factory),
                            [
                                (new MenuItem('disabled', $factory))
                                    ->setDisplay(false),
                                (new MenuItem('disabled2', $factory))
                                    ->setDisplay(false),
                            ]
                        )
                    ]
                )
            ],
        ];
    }

    /**
     * @param MenuItem $item
     * @param array $children
     *
     * @return MenuItem
     */
    protected function addChildren(MenuItem $item, array $children)
    {
        foreach ($children as $child) {
            $item->addChild($child);
        }

        return $item;
    }
}
