<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Manager;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateMoveManager;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UIBundle\Model\TreeItem;

class MenuUpdateMoveManagerTest extends WebTestCase
{
    private const MENU_NAME = 'application_menu';

    private MenuUpdateMoveManager $manager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([MenuUpdateData::class]);

        $this->manager = self::getContainer()->get('oro_navigation.manager.menu_update.move');
    }

    public function testMoveMenuItem(): void
    {
        $updates = $this->manager->moveMenuItem(
            $this->getMenu(),
            MenuUpdateData::MENU_UPDATE_2_1,
            $this->getScope(),
            MenuUpdateData::MENU_UPDATE_1,
            1
        );

        self::assertCount(3, $updates);

        self::assertEquals(MenuUpdateData::MENU_UPDATE_1_1, $updates[0]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_2_1, $updates[1]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_1, $updates[2]->getKey());

        self::assertEquals(MenuUpdateData::MENU_UPDATE_1, $updates[0]->getParentKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_1, $updates[1]->getParentKey());

        self::assertEquals(0, $updates[0]->getPriority());
        self::assertEquals(1, $updates[1]->getPriority());
    }

    public function testMoveMenuItems(): void
    {
        $updates = $this->manager->moveMenuItems(
            $this->getMenu(),
            [
                new TreeItem(MenuUpdateData::MENU_UPDATE_2_1),
                new TreeItem(MenuUpdateData::MENU_UPDATE_2_1_1),
            ],
            $this->getScope(),
            MenuUpdateData::MENU_UPDATE_1,
            0
        );

        self::assertCount(4, $updates);

        self::assertEquals(MenuUpdateData::MENU_UPDATE_1_1, $updates[0]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_2_1, $updates[1]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_2_1_1, $updates[2]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_1, $updates[3]->getKey());

        self::assertEquals(MenuUpdateData::MENU_UPDATE_1, $updates[0]->getParentKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_1, $updates[1]->getParentKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_1, $updates[2]->getParentKey());
        self::assertNull($updates[3]->getParentKey());

        self::assertEquals(0, $updates[1]->getPriority());
        self::assertEquals(1, $updates[2]->getPriority());
        self::assertEquals(2, $updates[0]->getPriority());
    }

    private function getScope(): Scope
    {
        /** @var ScopeManager $scopeManager */
        $scopeManager = self::getContainer()->get('oro_scope.scope_manager');

        return $scopeManager->findOrCreate('menu_default_visibility', []);
    }

    private function getMenu(): ItemInterface
    {
        return self::getContainer()->get('oro_menu.builder_chain')->get(self::MENU_NAME);
    }
}
