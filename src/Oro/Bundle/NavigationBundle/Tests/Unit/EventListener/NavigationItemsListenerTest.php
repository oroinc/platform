<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\EventListener\NavigationItemsListener;

class NavigationItemsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NavigationItemsListener */
    private $navigationListener;

    protected function setUp(): void
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker->expects($this->any())
            ->method('getDisabledResourcesByType')
            ->willReturnMap([[
                'navigation_items',
                [
                    'root.child2.disabled',
                    'root > child2_1 > disabled',
                    'root.child2_2.disabled',
                    'root > child2_3 > disabled',
                    'root.child3.disabled',
                    'root.child3.disabled2',
                    'root > child3_1 > disabled',
                    'root > child3_1 > disabled2',
                    'root.child3_2.disabled',
                    'root.child3_2.disabled2',
                    'root > child3_3 > disabled',
                    'root > child3_3 > disabled2',
                ],
            ],
        ]);

        $this->navigationListener = new NavigationItemsListener($featureChecker);
    }

    /**
     * @dataProvider onNavigationConfigureProvider
     */
    public function testOnNavigationConfigure(ConfigureMenuEvent $event, MenuItem $expected)
    {
        $this->navigationListener->onNavigationConfigure($event);
        $this->assertEquals($expected, $event->getMenu());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onNavigationConfigureProvider(): array
    {
        $factory = $this->createMock(FactoryInterface::class);

        return [
            [
                new ConfigureMenuEvent(
                    $factory,
                    $this->addChildren(
                        $this->getMenuItem('root', $factory),
                        [
                            $this->getMenuItem('child1', $factory),
                            // unclickable menu with enabled item
                            $this->addChildren(
                                $this->getMenuItem('child2', $factory, '#'),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('enabled', $factory),
                                ]
                            ),
                            // unclickable menu with enabled item with ' > ' delimiter
                            $this->addChildren(
                                $this->getMenuItem('child2_1', $factory, '#'),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('enabled', $factory),
                                ]
                            ),
                            // clickable menu with enabled item
                            $this->addChildren(
                                $this->getMenuItem('child2_2', $factory),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('enabled', $factory),
                                ]
                            ),
                            // clickable menu with enabled item with ' > ' delimiter
                            $this->addChildren(
                                $this->getMenuItem('child2_3', $factory),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('enabled', $factory),
                                ]
                            ),
                            // unclickable menu without enabled item
                            $this->addChildren(
                                $this->getMenuItem('child3', $factory, '#'),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('disabled2', $factory),
                                ]
                            ),
                            // unclickable menu without enabled item with ' > ' delimiter
                            $this->addChildren(
                                $this->getMenuItem('child3_1', $factory, '#'),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('disabled2', $factory),
                                ]
                            ),
                            // clickable menu without enabled item
                            $this->addChildren(
                                $this->getMenuItem('child3_2', $factory),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('disabled2', $factory),
                                ]
                            ),
                            // clickable menu without enabled item with ' > ' delimiter
                            $this->addChildren(
                                $this->getMenuItem('child3_3', $factory),
                                [
                                    $this->getMenuItem('disabled', $factory),
                                    $this->getMenuItem('disabled2', $factory),
                                ]
                            )
                        ]
                    )
                ),
                $this->addChildren(
                    $this->getMenuItem('root', $factory),
                    [
                        $this->getMenuItem('child1', $factory),
                        // unclickable menu with enabled item
                        $this->addChildren(
                            $this->getMenuItem('child2', $factory, '#'),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('enabled', $factory),
                            ]
                        ),
                        // unclickable menu with enabled item with ' > ' delimiter
                        $this->addChildren(
                            $this->getMenuItem('child2_1', $factory, '#'),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('enabled', $factory),
                            ]
                        ),
                        // clickable menu with enabled item
                        $this->addChildren(
                            $this->getMenuItem('child2_2', $factory),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('enabled', $factory),
                            ]
                        ),
                        // clickable menu with enabled item with ' > ' delimiter
                        $this->addChildren(
                            $this->getMenuItem('child2_3', $factory),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('enabled', $factory),
                            ]
                        ),
                        // unclickable menu without enabled item
                        $this->addChildren(
                            $this->getMenuItem('child3', $factory, '#', false),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('disabled2', $factory, null, false),
                            ]
                        ),
                        // unclickable menu without enabled item with ' > ' delimiter
                        $this->addChildren(
                            $this->getMenuItem('child3_1', $factory, '#', false),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('disabled2', $factory, null, false),
                            ]
                        ),
                        // clickable menu without enabled item
                        $this->addChildren(
                            $this->getMenuItem('child3_2', $factory),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('disabled2', $factory, null, false),
                            ]
                        ),
                        // clickable menu without enabled item with ' > ' delimiter
                        $this->addChildren(
                            $this->getMenuItem('child3_3', $factory),
                            [
                                $this->getMenuItem('disabled', $factory, null, false),
                                $this->getMenuItem('disabled2', $factory, null, false),
                            ]
                        )
                    ]
                )
            ],
        ];
    }

    private function getMenuItem(
        string $name,
        FactoryInterface $factory,
        ?string $uri = null,
        bool $display = true
    ): MenuItem {
        $item = new MenuItem($name, $factory);
        if (null !== $uri) {
            $item->setUri($uri);
        }
        $item->setDisplay($display);

        return $item;
    }

    private function addChildren(MenuItem $item, array $children): MenuItem
    {
        foreach ($children as $child) {
            $item->addChild($child);
        }

        return $item;
    }
}
