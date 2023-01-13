<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Factory;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler;
use Oro\Bundle\NavigationBundle\MenuUpdate\Factory\MenuUpdateFactory;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class MenuUpdateFactoryTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private const MENU = 'sample_menu';

    private MenuUpdateFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MenuUpdateFactory(new PropertyAccessor(), MenuUpdateStub::class);
    }

    /**
     * @dataProvider createMenuUpdateDataProvider
     */
    public function testCreateMenuUpdate(array $options, MenuUpdateInterface $expected): void
    {
        $scope = $this->createMock(Scope::class);
        $menuUpdate = $this->factory->createMenuUpdate(self::MENU, $scope, $options);

        if (!isset($options['key'])) {
            $expected->setKey($menuUpdate->getKey());
        }

        self::assertEquals($expected, $menuUpdate);
    }

    public function createMenuUpdateDataProvider(): array
    {
        return [
            'empty options' => [
                'options' => [],
                'expected' => (new MenuUpdateStub())
                    ->setMenu(self::MENU)
                    ->setScope($this->createMock(Scope::class)),
            ],
            'with key' => [
                'options' => ['key' => 'sample_key'],
                'expected' => (new MenuUpdateStub())
                    ->setMenu(self::MENU)
                    ->setScope($this->createMock(Scope::class))
                    ->setKey('sample_key'),
            ],
            'with parentKey' => [
                'options' => ['key' => 'sample_key', 'parentKey' => 'sample_parent_key'],
                'expected' => (new MenuUpdateStub())
                    ->setMenu(self::MENU)
                    ->setScope($this->createMock(Scope::class))
                    ->setKey('sample_key')
                    ->setParentKey('sample_parent_key'),
            ],
            'with divider' => [
                'options' => ['key' => 'sample_key', 'parentKey' => 'sample_parent_key', 'divider' => true],
                'expected' => (new MenuUpdateStub())
                    ->setMenu(self::MENU)
                    ->setScope($this->createMock(Scope::class))
                    ->setKey('sample_key')
                    ->setParentKey('sample_parent_key')
                    ->setDivider(true)
                    ->setUri('#')
                    ->setDefaultTitle(MenuUpdateTreeHandler::MENU_ITEM_DIVIDER_LABEL),
            ],
        ];
    }
}
