<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Menu\DividerBuilder;

class DividerBuilderTest extends \PHPUnit\Framework\TestCase
{
    private DividerBuilder $dividerBuilder;

    protected function setUp(): void
    {
        $this->dividerBuilder = new DividerBuilder();
    }

    /**
     * @dataProvider buildDataProvider
     */
    public function testBuild(ItemInterface $menuItem, array $expected): void
    {
        $this->dividerBuilder->build($menuItem);

        self::assertEquals($expected, $this->normalizeMenuItem($menuItem));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildDataProvider(): array
    {
        return [
            'not divider' => [
                'menuItem' => new MenuItem('sample_menu', new MenuFactory()),
                'expected' => [
                    'name' => 'sample_menu',
                    'attributes' => [],
                    'extras' => [],
                    'children' => [],
                ],
            ],
            'no dividers among children' => [
                'menuItem' => (new MenuItem('sample_menu', new MenuFactory()))
                    ->addChild('item1')
                    ->addChild('item1_1')
                    ->getParent()
                    ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'attributes' => [],
                    'extras' => [],
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'attributes' => [],
                            'extras' => [],
                            'children' => [
                                'item1_1' => [
                                    'name' => 'item1_1',
                                    'attributes' => [],
                                    'extras' => [],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'with divider' => [
                'menuItem' => (new MenuItem('sample_menu', new MenuFactory()))
                    ->addChild('item1')
                    ->getParent()
                    ->addChild('item2', ['extras' => ['divider' => true]])
                    ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'attributes' => [],
                    'extras' => [],
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'attributes' => [],
                            'extras' => [],
                            'children' => [],
                        ],
                        'item2' => [
                            'name' => 'item2',
                            'attributes' => [
                                'class' => 'divider',
                            ],
                            'extras' => [
                                'divider' => true,
                            ],
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'with divider and class' => [
                'menuItem' => (new MenuItem('sample_menu', new MenuFactory()))
                    ->addChild('item1')
                    ->getParent()
                    ->addChild('item2', ['extras' => ['divider' => true]])
                    ->setAttribute('class', 'sample-class')
                    ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'attributes' => [],
                    'extras' => [],
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'attributes' => [],
                            'extras' => [],
                            'children' => [],
                        ],
                        'item2' => [
                            'name' => 'item2',
                            'attributes' => ['class' => 'sample-class divider'],
                            'extras' => ['divider' => true],
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'with divider on 2nd level and class' => [
                'menuItem' => (new MenuItem('sample_menu', new MenuFactory()))
                    ->addChild('item1')
                    ->addChild('item1_1', ['extras' => ['divider' => true]])
                    ->setAttribute('class', 'sample-class')
                    ->getParent()
                    ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'attributes' => [],
                    'extras' => [],
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'attributes' => [],
                            'extras' => [],
                            'children' => [
                                'item1_1' => [
                                    'name' => 'item1_1',
                                    'attributes' => ['class' => 'sample-class divider'],
                                    'extras' => ['divider' => true],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function normalizeMenuItem(ItemInterface $menuItem): array
    {
        $result = [
            'name' => $menuItem->getName(),
            'extras' => $menuItem->getExtras(),
            'attributes' => $menuItem->getAttributes(),
        ];

        $result['children'] = [];
        foreach ($menuItem->getChildren() as $childMenuItem) {
            $result['children'][$childMenuItem->getName()] = $this->normalizeMenuItem($childMenuItem);
        }

        return $result;
    }
}
