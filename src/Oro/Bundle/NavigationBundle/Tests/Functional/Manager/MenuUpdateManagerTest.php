<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Knp\Menu\MenuItem;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @dbIsolation
 */
class MenuUpdateManagerTest extends WebTestCase
{
    use EntityTrait;

    const MENU_NAME = 'application_menu';

    /** @var EntityManager */
    protected $em;

    /** @var EntityRepository */
    protected $repository;

    /** @var MenuUpdateManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadMenuUpdateData::class
            ]
        );

        $this->manager = $this->getContainer()->get('oro_navigation.manager.menu_update');
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroNavigationBundle:MenuUpdate');
        $this->repository = $this->em->getRepository('OroNavigationBundle:MenuUpdate');
    }

    public function testGetMenu()
    {
        $menu = $this->manager->getMenu('application_menu');
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertGreaterThan(0, $menu->getChildren());
        $this->assertEquals('application_menu', $menu->getName());

        $menu = $this->manager->getMenu('not_existing_menu');
        $this->assertInstanceOf(MenuItem::class, $menu);
        $this->assertCount(0, $menu->getChildren());
        $this->assertEquals('not_existing_menu', $menu->getName());
    }

    public function testCreateMenuUpdate()
    {
        $scope = $this->getScope();

        $actualMenuUpdate = $this->manager->createMenuUpdate(
            $scope,
            [
                'key' => 'unique_item_key',
                'custom' => true,
                'menu' => 'application_menu',
                'parentKey' => null,
                'isDivider' => false
            ]
        );
        $expectedMenuUpdate = new MenuUpdate();
        $expectedMenuUpdate->setKey('unique_item_key')
            ->setCustom(true)
            ->setMenu('application_menu')
            ->setParentKey(null)
            ->setDivider(false)
            ->setScope($scope);
        $this->assertEquals($expectedMenuUpdate, $actualMenuUpdate);
    }

    public function testFindOrCreateMenuUpdate()
    {
        $scope = $this->getScope();

        $expectedMenuUpdate = new MenuUpdate();
        $expectedMenuUpdate->setKey('unique_item_key')
            ->setCustom(true)
            ->setMenu('application_menu')
            ->setParentKey(null)
            ->setScope($scope)
            ->setDivider(false);

        $actualMenuUpdate = $this->manager->findOrCreateMenuUpdate('application_menu', 'unique_item_key', $scope);
        $this->assertEquals($expectedMenuUpdate, $actualMenuUpdate);
    }

    public function testShowMenuItem()
    {
        $scope = $this->getScope();
        $this->manager->showMenuItem(self::MENU_NAME, LoadMenuUpdateData::MENU_UPDATE_2_1, $scope);

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => self::MENU_NAME,
                    'key' => [
                        LoadMenuUpdateData::MENU_UPDATE_2,
                        LoadMenuUpdateData::MENU_UPDATE_2_1,
                        LoadMenuUpdateData::MENU_UPDATE_2_1_1
                    ],
                    'scope' => $scope,
                ]
            );

        foreach ($result as $entity) {
            $this->assertTrue($entity->isActive());
        }
    }

    public function testHideMenuItem()
    {
        $scope = $this->getScope();
        $this->manager->hideMenuItem(self::MENU_NAME, LoadMenuUpdateData::MENU_UPDATE_1, $scope);

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => self::MENU_NAME,
                    'key' => [LoadMenuUpdateData::MENU_UPDATE_1, LoadMenuUpdateData::MENU_UPDATE_1_1],
                    'scope' => $scope
                ]
            );

        foreach ($result as $entity) {
            $this->assertFalse($entity->isActive());
        }
    }

    public function testMoveMenuItem()
    {
        $updates = $this->manager->moveMenuItem(
            self::MENU_NAME,
            LoadMenuUpdateData::MENU_UPDATE_3_1,
            $this->getScope(),
            LoadMenuUpdateData::MENU_UPDATE_2,
            0
        );

        $this->assertCount(2, $updates);

        $this->assertEquals(0, $updates[0]->getPriority());
        $this->assertEquals(LoadMenuUpdateData::MENU_UPDATE_3_1, $updates[0]->getKey());
        $this->assertEquals(LoadMenuUpdateData::MENU_UPDATE_2, $updates[0]->getParentKey());

        $this->assertEquals(LoadMenuUpdateData::MENU_UPDATE_2_1, $updates[1]->getKey());
        $this->assertEquals(1, $updates[1]->getPriority());
    }

    public function testDeleteMenuUpdates()
    {
        $scope = $this->getScope();
        $this->manager->deleteMenuUpdates($scope, self::MENU_NAME);

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => self::MENU_NAME,
                    'scope' => $scope
                ]
            );

        $this->assertCount(0, $result);
    }

    public function testGetRepository()
    {
        $repository = $this->manager->getRepository();
        $this->assertInstanceOf(MenuUpdateRepository::class, $repository);
    }

    /**
     * @return \Oro\Bundle\ScopeBundle\Entity\Scope
     */
    protected function getScope()
    {
        $scopeType = $this->getContainer()->getParameter('oro_navigation.menu_update.scope_type');

        return $this->getContainer()->get('oro_scope.scope_manager')->findOrCreate($scopeType, []);
    }
}
