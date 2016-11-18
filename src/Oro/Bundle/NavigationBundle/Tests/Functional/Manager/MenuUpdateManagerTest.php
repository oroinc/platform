<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

//ToDo: fix functional tests after resolve BB-5469

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
                'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData'
            ]
        );

        $this->manager = $this->getContainer()->get('oro_navigation.manager.menu_update_default');
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroNavigationBundle:MenuUpdate');
        $this->repository = $this->em->getRepository('OroNavigationBundle:MenuUpdate');
    }

    public function testGetMenuUpdatesByMenuAndScopeOwnershipGlobal()
    {
        $updates = $this->manager->getMenuUpdatesByMenuAndScope(self::MENU_NAME, 'global', 0);

        $this->assertCount(5, $updates);
    }

    public function testGetMenuUpdatesByMenuAndScopeOwnershipUser()
    {
        $updates = $this->manager->getMenuUpdatesByMenuAndScope(
            self::MENU_NAME,
            'user',
            $this->getReference('simple_user')->getId()
        );

        $this->assertCount(2, $updates);
    }

    public function testGetMenuUpdateByKeyAndScopeGlobal()
    {
        $update = $this->manager->getMenuUpdateByKeyAndScope(
            self::MENU_NAME,
            LoadMenuUpdateData::MENU_UPDATE_1,
            'global',
            0
        );

        $result = $this->repository
            ->findOneBy(
                [
                    'menu' => self::MENU_NAME,
                    'key' => LoadMenuUpdateData::MENU_UPDATE_1,
                    'ownershipType' => 'global',
                    'ownerId' => 0
                ]
            );

        $this->assertEquals($result, $update);
    }

    public function testGetMenuUpdateByKeyAndScopeUser()
    {
        $update = $this->manager->getMenuUpdateByKeyAndScope(
            self::MENU_NAME,
            LoadMenuUpdateData::MENU_UPDATE_3,
            'user',
            $this->getReference('simple_user')->getId()
        );

        $result = $this->repository
            ->findOneBy(
                [
                    'menu' => self::MENU_NAME,
                    'key' => LoadMenuUpdateData::MENU_UPDATE_3,
                    'ownershipType' => 'user',
                    'ownerId' => $this->getReference('simple_user')->getId()
                ]
            );

        $this->assertEquals($result, $update);
    }

    public function testShowMenuItem()
    {
        $this->manager->showMenuItem(self::MENU_NAME, LoadMenuUpdateData::MENU_UPDATE_2_1, 'global', 0);

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
                    'ownershipType' => 'global',
                    'ownerId' => 0
                ]
            );

        foreach ($result as $entity) {
            $this->assertTrue($entity->isActive());
        }
    }

    public function testHideMenuItem()
    {
        $this->manager->hideMenuItem(self::MENU_NAME, LoadMenuUpdateData::MENU_UPDATE_1, 'global', 0);

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => self::MENU_NAME,
                    'key' => [LoadMenuUpdateData::MENU_UPDATE_1, LoadMenuUpdateData::MENU_UPDATE_1_1],
                    'ownershipType' => 'global',
                    'ownerId' => 0
                ]
            );

        foreach ($result as $entity) {
            $this->assertFalse($entity->isActive());
        }
    }

    public function testResetMenuUpdatesWithOwnershipType()
    {
        $this->manager->resetMenuUpdatesWithOwnershipType('global', 0, self::MENU_NAME);

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => self::MENU_NAME,
                    'ownershipType' => 'global',
                    'ownerId' => 0
                ]
            );

        $this->assertCount(0, $result);
    }

    public function testMoveMenuItem()
    {
        $updates = $this->manager->moveMenuItem(
            self::MENU_NAME,
            LoadMenuUpdateData::MENU_UPDATE_3_1,
            'global',
            0,
            LoadMenuUpdateData::MENU_UPDATE_2,
            0
        );

        $this->assertCount(2, $updates);

        $this->assertEquals(1, $updates[0]->getPriority());
        $this->assertEquals(LoadMenuUpdateData::MENU_UPDATE_3_1, $updates[0]->getKey());
        $this->assertEquals(LoadMenuUpdateData::MENU_UPDATE_2, $updates[0]->getParentKey());

        $this->assertEquals(LoadMenuUpdateData::MENU_UPDATE_2_1, $updates[1]->getKey());
        $this->assertEquals(2, $updates[1]->getPriority());
    }
}
