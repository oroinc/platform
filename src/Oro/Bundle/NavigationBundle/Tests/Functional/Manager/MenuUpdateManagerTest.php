<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityRepository;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UIBundle\Model\TreeItem;

class MenuUpdateManagerTest extends WebTestCase
{
    private const MENU_NAME = 'application_menu';

    private EntityRepository $repository;

    private MenuUpdateManager $manager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([MenuUpdateData::class]);

        $this->manager = self::getContainer()->get('oro_navigation.manager.menu_update');
        $this->repository = self::getContainer()->get('doctrine')->getRepository(MenuUpdate::class);
    }

    public function testCreateMenuUpdate(): void
    {
        $scope = $this->getScope();
        $actualMenuUpdate = $this->manager->createMenuUpdate(
            $this->getMenu(),
            [
                'key' => 'unique_item_key',
                'custom' => true,
                'menu' => 'application_menu',
                'scope' => $scope,
                'parentKey' => null,
                'isDivider' => false,
            ]
        );
        $expectedMenuUpdate = new MenuUpdate();
        $expectedMenuUpdate->setKey('unique_item_key')
            ->setCustom(true)
            ->setMenu('application_menu')
            ->setParentKey(null)
            ->setDivider(false)
            ->setScope($scope);
        self::assertEquals($expectedMenuUpdate, $actualMenuUpdate);
    }

    public function testUpdateMenuUpdate(): void
    {
        $menu = $this->getMenu();
        $item = MenuUpdateUtils::findMenuItem($menu, 'oro_organization_list');
        $update = new MenuUpdate();
        $update->setKey('oro_organization_list')
            ->setParentKey('dashboard_tab');

        $this->manager->updateMenuUpdate($update, $item, 'menu');
        self::assertEquals('oro_organization_list', $update->getKey());
        self::assertEquals('dashboard_tab', $update->getParentKey());
    }

    public function testUpdateMenuUpdateWhenTranslateDisabled(): void
    {
        $menu = $this->getMenu();
        $item = MenuUpdateUtils::findMenuItem($menu, 'oro_organization_list');
        $item->setExtra('translate_disabled', true);

        $update = new MenuUpdate();
        $update->setKey('oro_organization_list')
            ->setParentKey('dashboard_tab');

        $this->manager->updateMenuUpdate($update, $item, 'menu');
        self::assertEquals($item->getLabel(), $update->getDefaultTitle());
        self::assertEquals('oro_organization_list', $update->getKey());
        self::assertEquals('dashboard_tab', $update->getParentKey());
    }

    public function testFindOrCreateMenuUpdate(): void
    {
        $scope = $this->getScope();
        $expectedMenuUpdate = new MenuUpdate();
        $expectedMenuUpdate->setKey('unique_item_key')
            ->setCustom(true)
            ->setMenu('application_menu')
            ->setParentKey(null)
            ->setScope($scope)
            ->setDivider(false);

        $actualMenuUpdate = $this->manager->findOrCreateMenuUpdate($this->getMenu(), 'unique_item_key', $scope);
        self::assertEquals($expectedMenuUpdate, $actualMenuUpdate);
    }

    public function testShowMenuItem(): void
    {
        $scope = $this->getScope();
        $this->manager->showMenuItem($this->getMenu(), MenuUpdateData::MENU_UPDATE_2_1, $scope);

        /** @var MenuUpdate[] $result */
        $result = $this->repository->findBy([
            'menu' => self::MENU_NAME,
            'key' => [
                MenuUpdateData::MENU_UPDATE_2,
                MenuUpdateData::MENU_UPDATE_2_1,
                MenuUpdateData::MENU_UPDATE_2_1_1,
            ],
            'scope' => $scope,
        ]);

        foreach ($result as $entity) {
            self::assertTrue($entity->isActive());
        }
    }

    public function testHideMenuItem(): void
    {
        $scope = $this->getScope();
        $this->manager->hideMenuItem($this->getMenu(), MenuUpdateData::MENU_UPDATE_1, $scope);

        /** @var MenuUpdate[] $result */
        $result = $this->repository->findBy([
            'menu' => self::MENU_NAME,
            'key' => [MenuUpdateData::MENU_UPDATE_1, MenuUpdateData::MENU_UPDATE_1_1],
            'scope' => $scope,
        ]);

        foreach ($result as $entity) {
            self::assertFalse($entity->isActive());
        }
    }

    public function testMoveMenuItem(): void
    {
        $updates = $this->manager->moveMenuItem(
            $this->getMenu(),
            MenuUpdateData::MENU_UPDATE_3_1,
            $this->getScope(),
            MenuUpdateData::MENU_UPDATE_2,
            0
        );

        self::assertCount(2, $updates);

        self::assertEquals(0, $updates[0]->getPriority());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_3_1, $updates[0]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_2, $updates[0]->getParentKey());

        self::assertEquals(MenuUpdateData::MENU_UPDATE_2_1, $updates[1]->getKey());
        self::assertEquals(1, $updates[1]->getPriority());
    }

    public function testMoveMenuItems(): void
    {
        $updates = $this->manager->moveMenuItems(
            $this->getMenu(),
            [
                new TreeItem(MenuUpdateData::MENU_UPDATE_3_1),
                new TreeItem(MenuUpdateData::MENU_UPDATE_3),
            ],
            $this->getScope(),
            MenuUpdateData::MENU_UPDATE_2,
            0
        );

        self::assertCount(3, $updates);

        self::assertEquals(MenuUpdateData::MENU_UPDATE_3_1, $updates[0]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_3, $updates[1]->getKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_2_1, $updates[2]->getKey());

        self::assertEquals(MenuUpdateData::MENU_UPDATE_2, $updates[0]->getParentKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_2, $updates[1]->getParentKey());
        self::assertEquals(MenuUpdateData::MENU_UPDATE_2, $updates[2]->getParentKey());

        self::assertEquals(0, $updates[0]->getPriority());
        self::assertEquals(1, $updates[1]->getPriority());
        self::assertEquals(2, $updates[2]->getPriority());
    }

    public function testDeleteMenuUpdates(): void
    {
        $scope = $this->getScope();
        $this->manager->deleteMenuUpdates($scope, self::MENU_NAME);

        /** @var MenuUpdate[] $result */
        $result = $this->repository->findBy(['menu' => self::MENU_NAME, 'scope' => $scope]);

        self::assertCount(0, $result);
    }

    public function testGetRepository(): void
    {
        $repository = $this->manager->getRepository();
        self::assertInstanceOf(MenuUpdateRepository::class, $repository);
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
