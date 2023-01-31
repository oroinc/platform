<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Menu\HideEmptyItemsBuilder;

class HideEmptyItemsBuilderTest extends \PHPUnit\Framework\TestCase
{
    private HideEmptyItemsBuilder $hideEmptyItemsBuilder;

    protected function setUp(): void
    {
        $this->hideEmptyItemsBuilder = new HideEmptyItemsBuilder();
    }

    /**
     * @dataProvider buildDataProvider
     */
    public function testBuild(ItemInterface $menuItem, array $expected): void
    {
        $this->hideEmptyItemsBuilder->build($menuItem);

        self::assertEquals($expected, $this->normalizeMenuItem($menuItem));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildDataProvider(): array
    {
        return [
            'displayed, without children' => [
                'menuItem' => (new MenuItem('sample_menu', new MenuFactory())),
                'expected' => [
                    'name' => 'sample_menu',
                    'extras' => [],
                    'display' => true,
                    'displayChildren' => true,
                    'children' => [],
                ],
            ],
            'displayed, with displayChildren == false' => [
                'menuItem' => (new MenuItem('sample_menu', new MenuFactory()))
                    ->setDisplayChildren(false)
                    ->addChild('item1')
                    ->setDisplay(false)
                    ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'extras' => [],
                    'display' => true,
                    'displayChildren' => false,
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'extras' => [],
                            'display' => false,
                            'displayChildren' => true,
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'displayed, with uri' => [
                'menuItem' => (new MenuItem('sample_menu', new MenuFactory()))
                    ->setUri('/sample/uri')
                    ->addChild('item1')
                    ->setDisplay(false)
                    ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'extras' => [],
                    'display' => true,
                    'displayChildren' => true,
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'extras' => [],
                            'display' => false,
                            'displayChildren' => true,
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'displayed, with children' => [
                'menuItem' =>
                    (new MenuItem('sample_menu', new MenuFactory()))
                        ->addChild('item1')
                        ->setExtra('isAllowed', true)
                        ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'extras' => [],
                    'display' => true,
                    'displayChildren' => true,
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'extras' => ['isAllowed' => true],
                            'display' => true,
                            'displayChildren' => true,
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'not displayed, with not allowed children' => [
                'menuItem' =>
                    (new MenuItem('sample_menu', new MenuFactory()))
                        ->addChild('item1')
                        ->setExtra('isAllowed', false)
                        ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'extras' => [],
                    'display' => false,
                    'displayChildren' => true,
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'extras' => ['isAllowed' => false],
                            'display' => true,
                            'displayChildren' => true,
                            'children' => [],
                        ],
                    ],
                ],
            ],
            'with # URI, not displayed, with not allowed children' => [
                'menuItem' =>
                    (new MenuItem('sample_menu', new MenuFactory()))
                        ->setUri('#')
                        ->addChild('item1')
                        ->setExtra('isAllowed', false)
                        ->getParent(),
                'expected' => [
                    'name' => 'sample_menu',
                    'extras' => [],
                    'display' => false,
                    'displayChildren' => true,
                    'children' => [
                        'item1' => [
                            'name' => 'item1',
                            'extras' => ['isAllowed' => false],
                            'display' => true,
                            'displayChildren' => true,
                            'children' => [],
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
            'display' => $menuItem->isDisplayed(),
            'displayChildren' => $menuItem->getDisplayChildren(),
        ];

        $result['children'] = [];
        foreach ($menuItem->getChildren() as $childMenuItem) {
            $result['children'][$childMenuItem->getName()] = $this->normalizeMenuItem($childMenuItem);
        }

        return $result;
    }
}
