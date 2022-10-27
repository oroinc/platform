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
    public function onNavigationConfigureProvider()
    {
        $factory = $this->createMock(FactoryInterface::class);

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
                            // unclickable menu with enabled item with ' > ' delimiter
                            $this->addChildren(
                                (new MenuItem('child2_1', $factory))
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
                            // clickable menu with enabled item with ' > ' delimiter
                            $this->addChildren(
                                new MenuItem('child2_3', $factory),
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
                            // unclickable menu without enabled item with ' > ' delimiter
                            $this->addChildren(
                                (new MenuItem('child3_1', $factory))
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
                            ),
                            // clickable menu without enabled item with ' > ' delimiter
                            $this->addChildren(
                                new MenuItem('child3_3', $factory),
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
                        // unclickable menu with enabled item with ' > ' delimiter
                        $this->addChildren(
                            (new MenuItem('child2_1', $factory))
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
                        // clickable menu with enabled item with ' > ' delimiter
                        $this->addChildren(
                            new MenuItem('child2_3', $factory),
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
                        // unclickable menu without enabled item with ' > ' delimiter
                        $this->addChildren(
                            (new MenuItem('child3_1', $factory))
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
                        ),
                        // clickable menu without enabled item with ' > ' delimiter
                        $this->addChildren(
                            new MenuItem('child3_3', $factory),
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

    private function addChildren(MenuItem $item, array $children): MenuItem
    {
        foreach ($children as $child) {
            $item->addChild($child);
        }

        return $item;
    }
}
