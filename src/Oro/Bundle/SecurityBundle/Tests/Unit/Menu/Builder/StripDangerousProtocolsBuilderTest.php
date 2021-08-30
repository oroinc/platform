<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Menu\Builder;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Oro\Bundle\SecurityBundle\Menu\Builder\StripDangerousProtocolsBuilder;
use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;

class StripDangerousProtocolsBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UriSecurityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $uriSecurityHelper;

    /** @var StripDangerousProtocolsBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->uriSecurityHelper = $this->createMock(UriSecurityHelper::class);

        $this->builder = new StripDangerousProtocolsBuilder($this->uriSecurityHelper);
    }

    /**
     * @dataProvider buildDataProvider
     */
    public function testBuild(ItemInterface $menuItem, ItemInterface $expectedMenuItem)
    {
        $this->uriSecurityHelper->expects($this->any())
            ->method('stripDangerousProtocols')
            ->willReturnMap([
                ['sample-data', 'sample-data'],
                ['', ''],
                ['sample-proto1:sample-data', 'sample-data'],
                ['sample-proto2:sample-data', 'sample-data'],
                ['sample-proto3:sample-data', 'sample-proto3:sample-data'],
            ]);

        $this->builder->build($menuItem);

        self::assertEquals($expectedMenuItem, $menuItem);
    }

    public function buildDataProvider(): array
    {
        return [
            'safe protocol is not stripped' => [
                'menuItem' => $this->mockMenuItem('menu_1', 'sample-proto3:sample-data', []),
                'expectedMenuItem' => $this->mockMenuItem('menu_1', 'sample-proto3:sample-data', []),
            ],
            'uri without protocol is not changed' => [
                'menuItem' => $this->mockMenuItem('menu_1', 'sample-data', []),
                'expectedMenuItem' => $this->mockMenuItem('menu_1', 'sample-data', []),
            ],
            'menu item with empty uri is not changed' => [
                'menuItem' => $this->mockMenuItem('menu_1', '', []),
                'expectedMenuItem' => $this->mockMenuItem('menu_1', '', []),
            ],
            'unsafe protocol with uppercase chars is stripped' => [
                'menuItem' => $this->mockMenuItem('menu_1', 'sample-proto1:sample-data', []),
                'expectedMenuItem' => $this->mockMenuItem('menu_1', 'sample-data', []),
            ],
            'unsafe protocol is stripped from both 1 level menu item and its children' => [
                'menuItem' => $this->mockMenuItem(
                    'menu_1',
                    'sample-proto1:sample-data',
                    [
                        $this->mockMenuItem('menu_1_1', 'sample-proto2:sample-data', []),
                        $this->mockMenuItem('menu_1_2', 'sample-proto3:sample-data', []),
                    ]
                ),
                'expectedMenuItem' => $this->mockMenuItem(
                    'menu_1',
                    'sample-data',
                    [
                        $this->mockMenuItem('menu_1_1', 'sample-data', []),
                        $this->mockMenuItem('menu_1_2', 'sample-proto3:sample-data', []),
                    ]
                ),
            ]
        ];
    }

    private function mockMenuItem(string $menuName, string $uri, array $children): ItemInterface
    {
        $menuItem = new MenuItem($menuName, $this->createMock(FactoryInterface::class));
        $menuItem->setUri($uri);
        $menuItem->setChildren($children);

        return $menuItem;
    }
}
