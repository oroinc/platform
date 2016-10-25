<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;


/**
 * @dbIsolation
 */
class MenuUpdateManagerTest extends WebTestCase
{
    use EntityTrait;

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
                'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData'
            ]
        );

        $this->manager = $this->getContainer()->get('oro_navigation.manager.menu_update_default');
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroNavigationBundle:MenuUpdate');
        $this->repository = $this->em->getRepository('OroNavigationBundle:MenuUpdate');
    }

    public function testGetMenuUpdatesByMenuAndScopeOwnershipGlobal()
    {
        $updates = $this->manager->getMenuUpdatesByMenuAndScope('application_menu', 'global', 0);

        $this->assertCount(5, $updates);
    }

    public function testGetMenuUpdatesByMenuAndScopeOwnershipUser()
    {
        $updates = $this->manager->getMenuUpdatesByMenuAndScope(
            'application_menu',
            'user',
            $this->getReference('simple_user')->getId()
        );

        $this->assertCount(2, $updates);
    }

    public function testGetMenuUpdateByKeyAndScopeGlobal()
    {
        $update = $this->manager->getMenuUpdateByKeyAndScope('application_menu', 'menu_update.1', 'global', 0);

        $result = $this->repository
            ->findOneBy(
                [
                    'menu' => 'application_menu',
                    'key' => 'menu_update.1',
                    'ownershipType' => 'global',
                    'ownerId' => 0
                ]
            );

        $this->assertEquals($result, $update);
    }

    public function testGetMenuUpdateByKeyAndScopeUser()
    {
        $update = $this->manager->getMenuUpdateByKeyAndScope(
            'application_menu',
            'menu_update.3',
            'user',
            $this->getReference('simple_user')->getId()
        );

        $result = $this->repository
            ->findOneBy(
                [
                    'menu' => 'application_menu',
                    'key' => 'menu_update.3',
                    'ownershipType' => 'user',
                    'ownerId' => $this->getReference('simple_user')->getId()
                ]
            );

        $this->assertEquals($result, $update);
    }

    public function testShowMenuItem()
    {
        $this->manager->showMenuItem('application_menu', 'menu_update.2_1', 'global', 0);

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => 'application_menu',
                    'key' => ['menu_update.2', 'menu_update.2_1', 'menu_update.2_1_1'],
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
        $this->manager->hideMenuItem('application_menu', 'menu_update.1', 'global', 0);

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => 'application_menu',
                    'key' => ['menu_update.1', 'menu_update.1_1'],
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
        $this->manager->resetMenuUpdatesWithOwnershipType('global', 0, 'application_menu');

        /** @var MenuUpdate[] $result */
        $result = $this->repository
            ->findBy(
                [
                    'menu' => 'application_menu',
                    'ownershipType' => 'global',
                    'ownerId' => 0
                ]
            );

        $this->assertCount(0, $result);
    }
}
