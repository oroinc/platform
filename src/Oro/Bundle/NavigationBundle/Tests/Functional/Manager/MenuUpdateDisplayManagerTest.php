<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityRepository;
use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateDisplayManager;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MenuUpdateDisplayManagerTest extends WebTestCase
{
    private const MENU_NAME = 'application_menu';

    private EntityRepository $repository;

    private MenuUpdateDisplayManager $manager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([MenuUpdateData::class]);

        $this->manager = self::getContainer()->get('oro_navigation.manager.menu_update.display');
        $this->repository = self::getContainer()->get('doctrine')->getRepository(MenuUpdate::class);
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
