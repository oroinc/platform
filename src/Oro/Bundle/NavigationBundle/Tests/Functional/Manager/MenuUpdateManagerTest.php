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

    public function testCreateMenuUpdateWhenCustom(): void
    {
        $scope = $this->getScope();
        $menu = $this->getMenu();

        $actualMenuUpdate = $this->manager->createMenuUpdate(
            $menu,
            $scope,
            [
                'key' => 'unique_item_key',
                'parentKey' => null,
                'divider' => false,
            ]
        );
        $expectedMenuUpdate = new MenuUpdate();
        $expectedMenuUpdate->setKey('unique_item_key')
            ->setCustom(true)
            ->setMenu(self::MENU_NAME)
            ->setParentKey(null)
            ->setDivider(false)
            ->setScope($scope)
            ->setPriority(count($menu->getChildren()));

        self::assertEquals($expectedMenuUpdate, $actualMenuUpdate);
    }

    public function testCreateMenuUpdateWhenNotCustom(): void
    {
        $menu = $this->getMenu();
        $item = MenuUpdateUtils::findMenuItem($menu, 'oro_organization_list');
        $item->setExtra('position', 42);

        $menuUpdate = $this->manager->createMenuUpdate(
            $this->getMenu(),
            $this->getScope(),
            ['key' => $item->getName()]
        );

        self::assertEquals($item->getName(), $menuUpdate->getKey());
        self::assertEquals($item->getParent()->getName(), $menuUpdate->getParentKey());
        self::assertEquals(42, $menuUpdate->getPriority());
    }

    public function testFindOrCreateMenuUpdate(): void
    {
        $scope = $this->getScope();
        $menu = $this->getMenu();

        $expectedMenuUpdate = new MenuUpdate();
        $expectedMenuUpdate->setKey('unique_item_key')
            ->setCustom(true)
            ->setMenu(self::MENU_NAME)
            ->setParentKey(null)
            ->setScope($scope)
            ->setDivider(false)
            ->setPriority(count($menu->getChildren()));

        $actualMenuUpdate = $this->manager->findOrCreateMenuUpdate(
            $menu,
            $scope,
            ['key' => 'unique_item_key']
        );

        self::assertEquals($expectedMenuUpdate, $actualMenuUpdate);
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
